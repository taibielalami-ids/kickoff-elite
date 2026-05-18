<?php

class AuthController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function showRegister(): void
    {
        $this->view('auth/register');
    }

    public function register(): void
    {
        $this->ensureCsrf('/auth/register');

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $role = 'user';
        $playingRoles = $_POST['playing_roles'] ?? [];
        if (!is_array($playingRoles)) {
            $playingRoles = [];
        }

        old_set($_POST);
        $errors = [];

        if ($username === '' || strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if (!$this->isAdult($dateOfBirth)) {
            $errors[] = 'You must be at least ' . config('app.min_age', 18) . ' years old.';
        }

        if ($city === '') {
            $errors[] = 'City is required.';
        }

        $allowedPlayingRoles = ['goalkeeper', 'defender', 'midfielder', 'attacker'];
        $cleanPlayingRoles = [];
        foreach ($playingRoles as $playingRole) {
            $value = trim((string) $playingRole);
            if (in_array($value, $allowedPlayingRoles, true) && !in_array($value, $cleanPlayingRoles, true)) {
                $cleanPlayingRoles[] = $value;
            }
        }
        if (empty($cleanPlayingRoles)) {
            $errors[] = 'Select at least one preferred playing role.';
        }

        if ($this->users->findByUsername($username)) {
            $errors[] = 'Username is already taken.';
        }

        if ($this->users->findByEmail($email)) {
            $errors[] = 'Email is already registered.';
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                flash_set('danger', $error);
            }
            redirect('/auth/register');
        }

        $userId = $this->users->create([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'date_of_birth' => $dateOfBirth,
            'city' => $city,
            'role' => $role,
        ]);
        $this->users->savePlayingRoles($userId, $cleanPlayingRoles);
        $this->users->markEmailVerified($userId);
        activity_log('account_registered', 'New account registration completed', 'user', $userId, $userId);

        old_clear();
        flash_set('success', 'Account created successfully. You can login now.');
        redirect('/auth/login');
    }

    public function showVerifyEmail(): void
    {
        $userId = (int) ($_GET['user'] ?? 0);
        $this->view('auth/verify-email', ['userId' => $userId]);
    }

    public function verifyEmail(): void
    {
        $this->ensureCsrf('/auth/login');

        $userId = (int) ($_POST['user_id'] ?? 0);
        $code = trim($_POST['code'] ?? '');

        if ($userId <= 0 || $code === '') {
            $this->redirectWith('/auth/login', 'danger', 'User and code are required.');
        }

        $found = $this->users->verifyAuthCode($userId, $code, 'verify_email');
        if (!$found) {
            $this->redirectWith('/auth/verify-email?user=' . $userId, 'danger', 'Invalid or expired email verification code.');
        }

        $this->users->markCodeUsed((int) $found['id']);
        $this->users->markEmailVerified($userId);
        activity_log('email_verified', 'Email verification completed', 'user', $userId, $userId);

        flash_set('success', 'Email verified successfully. You can now login.');
        redirect('/auth/login');
    }

    public function resendVerifyEmailCode(): void
    {
        $this->ensureCsrf('/auth/login');

        $userId = (int) ($_POST['user_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $user = null;
        if ($userId > 0) {
            $user = $this->users->findById($userId);
        }
        if (!$user && $email !== '') {
            $user = $this->users->findByEmail($email);
        }

        if (!$user) {
            $this->redirectWith('/auth/register', 'danger', 'No account found. Please register first with your email.');
        }

        if ((int) $user['email_verified'] === 1) {
            $this->redirectWith('/auth/login', 'info', 'This email is already verified. You can login now.');
        }

        $this->issueAndNotifyCode((int) $user['id'], (string) $user['email'], 'verify_email', 20);
        flash_set('success', 'Verification code has been re-sent.');
        redirect('/auth/verify-email?user=' . (int) $user['id']);
    }

    public function showLogin(): void
    {
        $this->view('auth/login');
    }

    public function login(): void
    {
        $this->ensureCsrf('/auth/login');

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        old_set($_POST);

        $user = $this->users->findByUsername($username);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->redirectWith('/auth/login', 'danger', 'Invalid username or password.');
        }

        if ((int) $user['email_verified'] !== 1) {
            $this->redirectWith('/auth/verify-email?user=' . $user['id'], 'danger', 'Please verify your email first.');
        }

        $this->issueAndNotifyCode((int) $user['id'], (string) $user['email'], 'login_otp', 10);
        $_SESSION['pending_login_user_id'] = (int) $user['id'];
        activity_log('login_step1_passed', 'Password accepted, waiting OTP', 'user', (int) $user['id'], (int) $user['id']);

        old_clear();
        flash_set('success', 'Second-step code sent to your email.');
        redirect('/auth/verify-login');
    }

    public function showVerifyLogin(): void
    {
        if (empty($_SESSION['pending_login_user_id'])) {
            $this->redirectWith('/auth/login', 'danger', 'Start login first.');
        }

        $this->view('auth/verify-login');
    }

    public function verifyLogin(): void
    {
        $this->ensureCsrf('/auth/login');

        $pendingUserId = (int) ($_SESSION['pending_login_user_id'] ?? 0);
        $code = trim($_POST['code'] ?? '');

        if ($pendingUserId <= 0 || $code === '') {
            $this->redirectWith('/auth/login', 'danger', 'Invalid verification request.');
        }

        $found = $this->users->verifyAuthCode($pendingUserId, $code, 'login_otp');
        if (!$found) {
            $this->redirectWith('/auth/verify-login', 'danger', 'Invalid or expired code.');
        }

        $this->users->markCodeUsed((int) $found['id']);
        $user = $this->users->findById($pendingUserId);
        if (!$user) {
            $this->redirectWith('/auth/login', 'danger', 'User not found.');
        }

        Auth::login($user);
        unset($_SESSION['pending_login_user_id']);
        activity_log('login_success', 'Two-step login successful', 'user', (int) $user['id'], (int) $user['id']);
        flash_set('success', 'Welcome back, ' . $user['username'] . '.');
        $this->redirectAfterLogin($user);
    }

    public function logout(): void
    {
        $this->ensureCsrf('/', 'Invalid request.');

        $currentUserId = (int) (Auth::id() ?? 0);
        if ($currentUserId > 0) {
            activity_log('logout', 'User logged out', 'user', $currentUserId, $currentUserId);
        }
        Auth::logout();
        flash_set('success', 'You have been logged out.');
        redirect('/');
    }

    private function isAdult(string $dateOfBirth): bool
    {
        if ($dateOfBirth === '') {
            return false;
        }

        $birthDate = DateTime::createFromFormat('Y-m-d', $dateOfBirth);
        if (!$birthDate) {
            return false;
        }

        $age = $birthDate->diff(new DateTime('today'))->y;
        return $age >= (int) config('app.min_age', 18);
    }

    private function issueAndNotifyCode(int $userId, string $email, string $codeType, int $minutes): void
    {
        $code = (string) random_int(100000, 999999);
        $this->users->saveAuthCode($userId, $code, $codeType, $minutes);

        $subject = 'Your ' . $this->codeLabel($codeType) . ' code';
        $message = "Your code is: {$code}\nThis code expires in {$minutes} minutes.";
        $sendResult = Mailer::sendText($email, $subject, $message);

        if (!($sendResult['ok'] ?? false)) {
            $error = trim((string) ($sendResult['error'] ?? ''));
            if ($error !== '') {
                flash_set('warning', 'Email delivery failed: ' . $error);
            }
            flash_set('info', 'Fallback code: ' . $code);
        }
    }

    private function codeLabel(string $codeType): string
    {
        if ($codeType === 'verify_email') {
            return 'email verification';
        }
        if ($codeType === 'login_otp') {
            return 'login verification';
        }
        return 'security';
    }

    private function redirectAfterLogin(array $user): never
    {
        $role = (string) ($user['role'] ?? '');
        if ($role === 'admin') {
            redirect('/admin/dashboard');
        }
        redirect('/dashboard');
    }
}


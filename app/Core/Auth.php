<?php

class Auth
{
    public static function user(): ?array
    {
        if (empty($_SESSION['auth_user'])) {
            return null;
        }
        return $_SESSION['auth_user'];
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        $_SESSION['auth_user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_user'], $_SESSION['pending_login_user_id']);
    }

    public static function require(array $roles = []): void
    {
        if (!self::check()) {
            flash_set('danger', 'Please login first.');
            redirect('/auth/login');
        }

        if (!empty($roles)) {
            $role = self::user()['role'] ?? '';
            if (!in_array($role, $roles, true)) {
                http_response_code(403);
                echo '403 - Access denied';
                exit;
            }
        }
    }
}

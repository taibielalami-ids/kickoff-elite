<?php

class BookingController extends Controller
{
    private Booking $bookings;

    public function __construct()
    {
        $this->bookings = new Booking();
    }

    public function index(): void
    {
        $this->requireAuth();
        $rows = $this->bookings->myBookings($this->userId());
        $this->view('bookings/index', [
            'bookings' => $rows,
        ]);
    }

    public function lock(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/pitches');

        $pitchId = (int) ($_POST['pitch_id'] ?? 0);
        $day = trim($_POST['day'] ?? '');
        $hour = trim($_POST['hour'] ?? '');
        $redirectQuery = trim($_POST['redirect_query'] ?? '');

        if ($pitchId <= 0 || !$this->hasDateAndHour($day, $hour)) {
            flash_set('danger', 'Select a valid day and hour before reserving.');
            $this->redirectToPitches($redirectQuery);
        }

        $slotStart = date('Y-m-d H:i:s', strtotime($day . ' ' . $hour));
        $slotEnd = date('Y-m-d H:i:s', strtotime($slotStart . ' +1 hour'));

        $userId = $this->userId();
        $result = $this->bookings->createLock($userId, $pitchId, $slotStart, $slotEnd);
        if (!($result['ok'] ?? false)) {
            flash_set('danger', (string) ($result['error'] ?? 'Could not lock slot.'));
            $this->redirectToPitches($redirectQuery);
        }

        activity_log('booking_slot_locked', 'Booking slot lock created', 'pitch', $pitchId, $userId);

        redirect('/bookings/confirm?lock=' . urlencode((string) $result['token']));
    }

    public function lockBulk(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/pitches');

        $pitchId = (int) ($_POST['pitch_id'] ?? 0);
        $day = trim($_POST['day'] ?? '');
        $hours = $_POST['hours'] ?? [];
        if (!is_array($hours)) {
            $hours = [];
        }

        if ($pitchId <= 0 || preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) !== 1) {
            $this->redirectWith('/pitches', 'danger', 'Invalid pitch/day for bulk reservation.');
        }

        $hours = array_values(array_unique(array_map(static fn ($v) => trim((string) $v), $hours)));
        $hours = array_values(array_filter($hours, static fn ($h) => preg_match('/^\d{2}:\d{2}$/', $h) === 1));
        sort($hours);

        if (empty($hours)) {
            $this->redirectWith('/pitches/profile?id=' . $pitchId . '&day=' . urlencode($day), 'warning', 'Select at least one slot.');
        }

        $tokens = [];
        $errors = [];
        $userId = $this->userId();

        foreach ($hours as $hour) {
            $slotStartTs = strtotime($day . ' ' . $hour);
            if ($slotStartTs === false) {
                $errors[] = $hour . ': invalid time value.';
                continue;
            }
            $slotStart = date('Y-m-d H:i:s', $slotStartTs);
            $slotEnd = date('Y-m-d H:i:s', strtotime($slotStart . ' +1 hour'));
            $result = $this->bookings->createLock($userId, $pitchId, $slotStart, $slotEnd);
            if (!($result['ok'] ?? false)) {
                $errors[] = $hour . ': ' . (string) ($result['error'] ?? 'Could not lock slot.');
                continue;
            }
            $tokens[] = (string) $result['token'];
        }

        foreach (array_slice($errors, 0, 4) as $error) {
            flash_set('warning', $error);
        }

        if (empty($tokens)) {
            $this->redirectWith('/pitches/profile?id=' . $pitchId . '&day=' . urlencode($day), 'danger', 'No selected slots could be locked. Please choose different slots.');
        }

        if (count($tokens) === 1) {
            redirect('/bookings/confirm?lock=' . urlencode($tokens[0]));
        }

        $_SESSION['bulk_lock_tokens'] = $tokens;
        $_SESSION['bulk_lock_from_pitch'] = $pitchId;
        $_SESSION['bulk_lock_day'] = $day;
        redirect('/bookings/confirm-bulk');
    }

    public function showConfirm(): void
    {
        $this->requireAuth();
        $token = trim($_GET['lock'] ?? '');
        if ($token === '') {
            $this->redirectWith('/pitches', 'danger', 'Invalid booking lock token.');
        }

        $lock = $this->bookings->findActiveLockByToken($token, $this->userId());
        if (!$lock) {
            $this->redirectWith('/pitches', 'danger', 'Lock expired. Please book again.');
        }

        $this->view('bookings/confirm', [
            'lock' => $lock,
        ]);
    }

    public function confirm(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/pitches');

        $token = trim($_POST['lock_token'] ?? '');
        $paymentMode = trim($_POST['payment_mode'] ?? 'wallet');
        if ($token === '') {
            $this->redirectWith('/pitches', 'danger', 'Missing lock token.');
        }

        $userId = $this->userId();
        $result = $this->bookings->confirmFromLock($token, $userId, $paymentMode);
        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/pitches', 'danger', (string) ($result['error'] ?? 'Booking confirmation failed.'));
        }

        $status = (string) ($result['status'] ?? 'reserved');
        $code = (string) ($result['booking_code'] ?? '');
        $pay = (string) ($result['payment_mode'] ?? 'wallet');
        $bookingId = (int) ($result['booking_id'] ?? 0);
        if ($bookingId > 0) {
            activity_log('booking_confirmed', 'Booking confirmed with status ' . $status, 'booking', $bookingId, $userId);
        }
        $this->redirectWith('/bookings', 'success', 'Booking created successfully. Status: ' . $status . '. Code: ' . $code . '. Payment: paid with ' . $pay . '.');
    }

    public function showConfirmBulk(): void
    {
        $this->requireAuth();
        $tokens = $_SESSION['bulk_lock_tokens'] ?? [];
        if (!is_array($tokens) || empty($tokens)) {
            $this->redirectWith('/pitches', 'warning', 'No pending slot selection found.');
        }

        $locks = [];
        $userId = $this->userId();
        foreach ($tokens as $token) {
            $lock = $this->bookings->findActiveLockByToken((string) $token, $userId);
            if ($lock) {
                $locks[] = $lock;
            }
        }

        if (empty($locks)) {
            unset($_SESSION['bulk_lock_tokens'], $_SESSION['bulk_lock_from_pitch'], $_SESSION['bulk_lock_day']);
            $this->redirectWith('/pitches', 'danger', 'All selected slot locks expired. Please select slots again.');
        }

        if (count($locks) === 1) {
            redirect('/bookings/confirm?lock=' . urlencode((string) $locks[0]['lock_token']));
        }

        $this->view('bookings/confirm-bulk', [
            'locks' => $locks,
            'fromPitchId' => (int) ($_SESSION['bulk_lock_from_pitch'] ?? 0),
            'fromDay' => (string) ($_SESSION['bulk_lock_day'] ?? ''),
        ]);
    }

    public function confirmBulk(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/bookings');

        $paymentMode = trim($_POST['payment_mode'] ?? 'wallet');
        if (!in_array($paymentMode, ['wallet', 'tickets'], true)) {
            $paymentMode = 'wallet';
        }

        $tokens = $_SESSION['bulk_lock_tokens'] ?? [];
        if (!is_array($tokens) || empty($tokens)) {
            $this->redirectWith('/pitches', 'warning', 'No pending slot selection found.');
        }

        $successCount = 0;
        $failures = [];
        $userId = $this->userId();
        foreach ($tokens as $token) {
            $result = $this->bookings->confirmFromLock((string) $token, $userId, $paymentMode);
            if ($result['ok'] ?? false) {
                $successCount++;
                continue;
            }
            $failures[] = (string) ($result['error'] ?? 'Booking confirmation failed.');
        }

        unset($_SESSION['bulk_lock_tokens'], $_SESSION['bulk_lock_from_pitch'], $_SESSION['bulk_lock_day']);

        if ($successCount > 0) {
            flash_set('success', 'Confirmed ' . $successCount . ' booking(s) successfully.');
        }
        foreach (array_slice($failures, 0, 4) as $failure) {
            flash_set('warning', $failure);
        }

        if ($successCount === 0) {
            $this->redirectWith('/pitches', 'danger', 'None of the selected slots could be confirmed.');
        }

        redirect('/bookings');
    }

    public function cancel(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/bookings');

        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        if ($bookingId <= 0) {
            $this->redirectWith('/bookings', 'danger', 'Invalid booking id.');
        }

        $userId = $this->userId();
        $result = $this->bookings->cancelByUser($bookingId, $userId);
        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/bookings', 'danger', (string) ($result['error'] ?? 'Could not cancel booking.'));
        }

        $refundedAmount = (float) ($result['refunded_amount'] ?? 0.00);
        $refundedTickets = (int) ($result['refunded_tickets'] ?? 0);
        $refundMessage = 'No refund was needed.';
        if ($refundedAmount > 0) {
            $refundMessage = 'Refunded ' . number_format($refundedAmount, 0) . ' DH to your wallet.';
        } elseif ($refundedTickets > 0) {
            $refundMessage = 'Refunded ' . $refundedTickets . ' ticket(s) to your wallet.';
        }

        activity_log('booking_cancelled', 'Booking cancelled by user', 'booking', $bookingId, $userId);
        $this->redirectWith('/bookings', 'success', 'Booking cancelled successfully. ' . $refundMessage);
    }

    public function pay(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/bookings');

        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $paymentMode = trim($_POST['payment_mode'] ?? 'wallet');
        if ($bookingId <= 0) {
            $this->redirectWith('/bookings', 'danger', 'Invalid booking id.');
        }

        $userId = $this->userId();
        $result = $this->bookings->payWaitingBooking($bookingId, $userId, $paymentMode);
        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/bookings', 'danger', (string) ($result['error'] ?? 'Could not pay booking.'));
        }

        activity_log('booking_paid', 'Waiting payment booking paid and reserved', 'booking', $bookingId, $userId);
        $this->redirectWith('/bookings', 'success', 'Booking paid successfully and status changed to reserved.');
    }

    private function hasDateAndHour(string $day, string $hour): bool
    {
        if ($day === '' || $hour === '') {
            return false;
        }

        $dayOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) === 1;
        $hourOk = preg_match('/^\d{2}:\d{2}$/', $hour) === 1;
        return $dayOk && $hourOk;
    }

    private function redirectToPitches(string $query): never
    {
        $target = '/pitches';
        if ($query !== '') {
            $target .= '?' . $query;
        }
        redirect($target);
    }
}

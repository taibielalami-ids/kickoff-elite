<?php

class AdminPitchController extends Controller
{
    private Booking $bookings;
    private Pitch $pitches;

    public function __construct()
    {
        $this->bookings = new Booking();
        $this->pitches = new Pitch();
    }

    public function pitchesIndex(): void
    {
        $this->requireAuth(['admin']);
        $pitches = $this->pitches->adminPitches();

        $this->view('admin/pitches-index', [
            'pitches' => $pitches,
            'isAdmin' => true,
        ]);
    }

    public function createPitchPage(): void
    {
        $this->requireAuth(['admin']);
        $this->view('admin/pitch-create');
    }

    public function storePitch(): void
    {
        $this->requireAuth(['admin']);
        $this->ensureCsrf('/admin/pitches/create');

        old_set($_POST);
        $name = trim($_POST['name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $lat = trim($_POST['lat'] ?? '');
        $lng = trim($_POST['lng'] ?? '');
        $openTime = trim($_POST['open_time'] ?? '');
        $closeTime = trim($_POST['close_time'] ?? '');
        $price = trim($_POST['price_per_player'] ?? '50');
        $status = trim($_POST['status'] ?? 'available');

        $errors = [];
        if ($name === '' || strlen($name) < 3) {
            $errors[] = 'Pitch name must be at least 3 characters.';
        }
        if ($city === '') {
            $errors[] = 'City is required.';
        }
        if ($address === '') {
            $errors[] = 'Address is required.';
        }
        if (!is_numeric($lat) || !is_numeric($lng)) {
            $errors[] = 'Latitude and longitude must be valid numbers.';
        }
        if (!$this->isValidTime($openTime) || !$this->isValidTime($closeTime)) {
            $errors[] = 'Open and close times are required.';
        }
        if ($this->isValidTime($openTime) && $this->isValidTime($closeTime) && $openTime >= $closeTime) {
            $errors[] = 'Close time must be after open time.';
        }
        if (!is_numeric($price) || (float) $price <= 0) {
            $errors[] = 'Price per player must be a positive number.';
        }
        if (!in_array($status, ['available', 'reserved', 'maintenance', 'lights_off', 'weather_closed'], true)) {
            $status = 'available';
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                flash_set('danger', $error);
            }
            redirect('/admin/pitches/create');
        }

        $user = Auth::user();
        $pitchId = $this->pitches->create([
            'owner_id' => (int) $user['id'],
            'name' => $name,
            'city' => $city,
            'address' => $address,
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'open_time' => $openTime,
            'close_time' => $closeTime,
            'price_per_player' => (float) $price,
            'team_size' => 10,
            'status' => $status,
        ]);

        old_clear();
        flash_set('success', 'Pitch created successfully.');
        redirect('/admin/pitches/manage?id=' . $pitchId);
    }

    public function managePitchPage(): void
    {
        $this->requireAuth(['admin']);
        $pitchId = (int) ($_GET['id'] ?? 0);

        if ($pitchId <= 0) {
            $this->redirectWith('/admin/pitches', 'danger', 'Invalid pitch id.');
        }

        $pitch = $this->loadManagedPitchOrRedirect($pitchId);

        $photos = $this->pitches->photosByPitch($pitchId);
        $blockedSlots = $this->pitches->blockedSlotsByPitch($pitchId);

        $this->view('admin/pitch-manage', [
            'pitch' => $pitch,
            'photos' => $photos,
            'blockedSlots' => $blockedSlots,
        ]);
    }

    public function updatePitch(): void
    {
        $this->requireAuth(['admin']);
        $this->ensureCsrf('/admin/pitches');

        $pitchId = (int) ($_POST['pitch_id'] ?? 0);
        $this->loadManagedPitchOrRedirect($pitchId);

        $name = trim($_POST['name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $lat = trim($_POST['lat'] ?? '');
        $lng = trim($_POST['lng'] ?? '');
        $openTime = trim($_POST['open_time'] ?? '');
        $closeTime = trim($_POST['close_time'] ?? '');
        $price = trim($_POST['price_per_player'] ?? '50');
        $status = trim($_POST['status'] ?? 'available');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $errors = [];
        if ($name === '' || strlen($name) < 3) {
            $errors[] = 'Pitch name must be at least 3 characters.';
        }
        if ($city === '' || $address === '') {
            $errors[] = 'City and address are required.';
        }
        if (!is_numeric($lat) || !is_numeric($lng)) {
            $errors[] = 'Latitude and longitude must be valid numbers.';
        }
        if (!$this->isValidTime($openTime) || !$this->isValidTime($closeTime) || $openTime >= $closeTime) {
            $errors[] = 'Opening hours are invalid.';
        }
        if (!is_numeric($price) || (float) $price <= 0) {
            $errors[] = 'Price per player must be valid.';
        }
        if (!in_array($status, ['available', 'reserved', 'maintenance', 'lights_off', 'weather_closed'], true)) {
            $status = 'available';
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                flash_set('danger', $error);
            }
            redirect('/admin/pitches/manage?id=' . $pitchId);
        }

        $this->pitches->update($pitchId, [
            'name' => $name,
            'city' => $city,
            'address' => $address,
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'open_time' => $openTime,
            'close_time' => $closeTime,
            'price_per_player' => (float) $price,
            'status' => $status,
            'is_active' => $isActive,
        ]);

        flash_set('success', 'Pitch updated successfully.');
        redirect('/admin/pitches/manage?id=' . $pitchId);
    }

    public function addPhoto(): void
    {
        $this->requireAuth(['admin']);
        $this->ensureCsrf('/admin/pitches', 'Invalid form token.');

        $pitchId = (int) ($_POST['pitch_id'] ?? 0);

        $this->loadManagedPitchOrRedirect($pitchId);

        $file = $_FILES['photo_file'] ?? null;
        $saved = $this->saveUploadedPitchPhoto($pitchId, $file);
        if (!($saved['ok'] ?? false)) {
            $this->redirectWith('/admin/pitches/manage?id=' . $pitchId, 'danger', (string) ($saved['error'] ?? 'Could not upload photo.'));
        }

        $photoUrl = (string) ($saved['url'] ?? '');
        $this->pitches->addPhoto($pitchId, $photoUrl);
        flash_set('success', 'Photo added.');
        redirect('/admin/pitches/manage?id=' . $pitchId);
    }

    public function deletePhoto(): void
    {
        $this->requireAuth(['admin']);
        $this->ensureCsrf('/admin/pitches', 'Invalid form token.');

        $pitchId = (int) ($_POST['pitch_id'] ?? 0);
        $photoId = (int) ($_POST['photo_id'] ?? 0);
        $this->loadManagedPitchOrRedirect($pitchId);

        $photos = $this->pitches->photosByPitch($pitchId);
        $photoUrl = '';
        foreach ($photos as $photo) {
            if ((int) ($photo['id'] ?? 0) === $photoId) {
                $photoUrl = (string) ($photo['photo_url'] ?? '');
                break;
            }
        }

        $this->pitches->deletePhoto($photoId, $pitchId);
        $this->deleteUploadedPitchPhotoFile($photoUrl);
        flash_set('success', 'Photo removed.');
        redirect('/admin/pitches/manage?id=' . $pitchId);
    }

    public function addBlockedSlot(): void
    {
        $this->requireAuth(['admin']);
        $this->ensureCsrf('/admin/pitches', 'Invalid form token.');

        $pitchId = (int) ($_POST['pitch_id'] ?? 0);
        $startAt = trim($_POST['start_at'] ?? '');
        $endAt = trim($_POST['end_at'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        $this->loadManagedPitchOrRedirect($pitchId);

        if (!$this->isValidDateTime($startAt) || !$this->isValidDateTime($endAt) || strtotime($startAt) >= strtotime($endAt)) {
            $this->redirectWith('/admin/pitches/manage?id=' . $pitchId, 'danger', 'Blocked slot date/time values are invalid.');
        }

        $startAtSql = $this->toSqlDateTime($startAt);
        $endAtSql = $this->toSqlDateTime($endAt);

        $this->pitches->addBlockedSlot($pitchId, $startAtSql, $endAtSql, $reason);
        flash_set('success', 'Blocked slot added.');
        redirect('/admin/pitches/manage?id=' . $pitchId);
    }

    public function deleteBlockedSlot(): void
    {
        $this->requireAuth(['admin']);
        $this->ensureCsrf('/admin/pitches', 'Invalid form token.');

        $pitchId = (int) ($_POST['pitch_id'] ?? 0);
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        $this->loadManagedPitchOrRedirect($pitchId);

        $this->pitches->deleteBlockedSlot($slotId, $pitchId);
        flash_set('success', 'Blocked slot removed.');
        redirect('/admin/pitches/manage?id=' . $pitchId);
    }

    public function verifyCodePage(): void
    {
        $this->requireAuth(['admin']);
        $this->view('admin/verify-code');
    }

    public function verifyCode(): void
    {
        $this->requireAuth(['admin']);
        $this->ensureCsrf('/admin/verify-code');

        $code = strtoupper(trim($_POST['code'] ?? ''));
        if ($code === '') {
            $this->redirectWith('/admin/verify-code', 'danger', 'Please enter a booking code.');
        }

        $found = $this->bookings->findByCodeForAdmin($code);

        if (!$found) {
            flash_set('danger', 'Code not found for your venue.');
            redirect('/admin/verify-code');
        }

        if (($found['code_status'] ?? '') === 'used') {
            flash_set('info', 'This code was already used at ' . $found['used_at']);
            $this->view('admin/verify-code', ['booking' => $found]);
            return;
        }

        if (($found['code_status'] ?? '') === 'cancelled' || ($found['booking_status'] ?? '') === 'cancelled') {
            flash_set('warning', 'This booking was cancelled. Check-in is not allowed.');
            $this->view('admin/verify-code', ['booking' => $found]);
            return;
        }

        if (($_POST['action'] ?? '') === 'confirm') {
            $this->bookings->markCodeUsed((int) $found['booking_code_id']);
            $found = $this->bookings->findByCodeForAdmin($code);
            flash_set('success', 'Check-in confirmed for booking code ' . $code . '.');
        }

        $this->view('admin/verify-code', ['booking' => $found]);
    }

    private function loadManagedPitchOrRedirect(int $pitchId): array
    {
        $pitch = $this->pitches->findForAdmin($pitchId);
        if (!$pitch) {
            $this->redirectWith('/admin/pitches', 'danger', 'Pitch not found.');
        }
        return $pitch;
    }

    private function isValidTime(string $time): bool
    {
        return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time) === 1;
    }

    private function isValidDateTime(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $value);
        return $dt instanceof DateTime;
    }

    private function toSqlDateTime(string $value): string
    {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $value);
        if (!$dt) {
            return $value;
        }
        return $dt->format('Y-m-d H:i:s');
    }

    private function saveUploadedPitchPhoto(int $pitchId, mixed $file): array
    {
        if (!is_array($file)) {
            return ['ok' => false, 'error' => 'No file was uploaded.'];
        }

        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed. Please select a valid image file.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['ok' => false, 'error' => 'Invalid uploaded file.'];
        }

        $sizeBytes = (int) ($file['size'] ?? 0);
        $maxBytes = 5 * 1024 * 1024;
        if ($sizeBytes <= 0 || $sizeBytes > $maxBytes) {
            return ['ok' => false, 'error' => 'Image must be smaller than 5 MB.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmpName);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'error' => 'Only JPG, PNG, WEBP, or GIF files are allowed.'];
        }

        $uploadDir = BASE_PATH . '/public/uploads/pitches';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            return ['ok' => false, 'error' => 'Could not create uploads directory.'];
        }

        $ext = $allowed[$mime];
        $safeName = 'pitch_' . $pitchId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $safeName;
        if (!move_uploaded_file($tmpName, $targetPath)) {
            return ['ok' => false, 'error' => 'Could not save uploaded image file.'];
        }

        $publicUrl = route_path('/uploads/pitches/' . $safeName);
        return ['ok' => true, 'url' => $publicUrl];
    }

    private function deleteUploadedPitchPhotoFile(string $photoUrl): void
    {
        $parsed = parse_url($photoUrl, PHP_URL_PATH);
        $path = is_string($parsed) ? $parsed : '';
        if ($path === '') {
            return;
        }

        $basePath = rtrim((string) config('app.base_path', ''), '/');
        if ($basePath !== '' && str_starts_with($path, $basePath . '/')) {
            $path = substr($path, strlen($basePath));
        }

        if (!str_starts_with($path, '/uploads/pitches/')) {
            return;
        }

        $filePath = BASE_PATH . '/public' . $path;
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }
}

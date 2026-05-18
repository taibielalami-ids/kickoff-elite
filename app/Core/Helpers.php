<?php

function config(string $key, mixed $default = null): mixed
{
    $parts = explode('.', $key);
    $value = $GLOBALS['config'] ?? [];

    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

function route_path(string $path = '/'): string
{
    $basePath = app_base_path();
    $normalized = '/' . ltrim($path, '/');
    if ($normalized === '//') {
        $normalized = '/';
    }
    return ($basePath === '') ? $normalized : $basePath . $normalized;
}

function app_base_path(): string
{
    $configured = trim((string) config('app.base_path', ''));
    if ($configured !== '' && strtolower($configured) !== 'auto') {
        return rtrim('/' . trim($configured, '/'), '/');
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if (str_ends_with($dir, '/public')) {
        $dir = substr($dir, 0, -7);
    }

    if ($dir === '' || $dir === '.' || $dir === '/') {
        return '';
    }

    return $dir;
}

function redirect(string $path): never
{
    header('Location: ' . route_path($path));
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

function flash_get(string $type): array
{
    $messages = $_SESSION['flash'][$type] ?? [];
    unset($_SESSION['flash'][$type]);
    return $messages;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

function old_set(array $data): void
{
    $_SESSION['old'] = $data;
}

function old_clear(): void
{
    unset($_SESSION['old']);
}

function activity_log(
    string $actionType,
    string $details = '',
    ?string $entityType = null,
    ?int $entityId = null,
    ?int $targetUserId = null
): void {
    try {
        if (!class_exists('Database')) {
            return;
        }

        $db = Database::connection();
        $actorUserId = null;
        if (class_exists('Auth') && Auth::check()) {
            $actorUserId = (int) Auth::id();
        }

        if ($targetUserId === null) {
            $targetUserId = $actorUserId;
        }

        $actionType = substr(trim($actionType), 0, 60);
        $details = substr(trim($details), 0, 255);
        $entityType = $entityType !== null ? substr(trim($entityType), 0, 40) : null;

        $ipAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 180);

        $actorUserIdValue = $actorUserId ?? 0;
        $targetUserIdValue = $targetUserId ?? 0;
        $entityIdValue = $entityId ?? 0;

        $sql = 'INSERT INTO activity_logs
                (actor_user_id, target_user_id, action_type, entity_type, entity_id, details, ip_address, user_agent, created_at)
                VALUES (NULLIF(?, 0), NULLIF(?, 0), ?, ?, NULLIF(?, 0), ?, ?, ?, NOW())';
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param(
            'iississs',
            $actorUserIdValue,
            $targetUserIdValue,
            $actionType,
            $entityType,
            $entityIdValue,
            $details,
            $ipAddress,
            $userAgent
        );
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
    }
}

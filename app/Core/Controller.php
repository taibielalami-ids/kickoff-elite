<?php

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        $viewFile = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("View {$view} not found.");
        }

        extract($data);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }

    protected function requireAuth(array $roles = []): void
    {
        Auth::require($roles);
    }

    protected function userId(): int
    {
        return (int) Auth::id();
    }

    protected function user(): array
    {
        return Auth::user();
    }

    protected function ensureCsrf(string $redirectPath, string $message = 'Invalid form token. Please try again.'): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash_set('danger', $message);
            redirect($redirectPath);
        }
    }

    protected function redirectWith(string $path, string $type, string $message): never
    {
        flash_set($type, $message);
        redirect($path);
    }
}

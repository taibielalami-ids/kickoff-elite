<?php

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable|array $handler): void
    {
        $normalized = $this->normalizePath($path);
        $this->routes[$method][$normalized] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $normalized = $this->normalizePath($uri);
        $handler = $this->routes[$method][$normalized] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '404 - Page not found';
            return;
        }

        if (is_callable($handler)) {
            call_user_func($handler);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $methodName] = $handler;
            $controller = new $class();
            $controller->$methodName();
            return;
        }

        throw new RuntimeException('Route handler is invalid.');
    }

    private function normalizePath(string $path): string
    {
        $trimmed = '/' . trim($path, '/');
        return $trimmed === '//' ? '/' : $trimmed;
    }
}


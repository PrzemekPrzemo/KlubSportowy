<?php

namespace App\Helpers;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes[] = ['GET', $path, $handler];
    }

    public function post(string $path, array $handler): void
    {
        $this->routes[] = ['POST', $path, $handler];
    }

    /** Register a route via ['METHOD', 'path', [Class, 'method']] tuple (plugin-friendly) */
    public function addRoute(string $method, string $path, array $handler): void
    {
        $this->routes[] = [strtoupper($method), $path, $handler];
    }

    public function dispatch(): void
    {
        // Defensywne: healthchecki / CLI / niektóre probe-y (kubernetes,
        // monitoring) wywołują front controller bez pełnego $_SERVER.
        // Brak REQUEST_METHOD/REQUEST_URI = 400 zamiast Fatal Error.
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $rawUri = $_SERVER['REQUEST_URI']    ?? null;
        if ($method === null || $rawUri === null) {
            http_response_code(400);
            echo 'Bad request';
            return;
        }

        $uri = parse_url($rawUri, PHP_URL_PATH) ?? '/';

        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = '/' . ltrim($uri, '/');

        foreach ($this->routes as [$routeMethod, $routePath, $handler]) {
            $pattern = $this->buildPattern($routePath);
            if ($routeMethod === $method && preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$controllerClass, $action] = $handler;
                $controller = new $controllerClass();
                $controller->$action(...array_values($params));
                return;
            }
        }

        http_response_code(404);
        $this->render404();
    }

    private function buildPattern(string $path): string
    {
        $pattern = preg_replace('#:([a-zA-Z_]+)#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function render404(): void
    {
        $view = ROOT_PATH . '/app/Views/errors/404.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<h1>404 – Strona nie istnieje</h1>';
        }
    }
}

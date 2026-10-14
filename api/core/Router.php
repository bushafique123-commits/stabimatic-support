<?php

final class Router
{
    private $basePaths;
    private $routes = [];

    public function __construct($basePath = '')
    {
        $paths = is_array($basePath) ? $basePath : [$basePath];
        $this->basePaths = array_values(array_unique(array_map(function ($path): string {
            $trimmed = trim((string) $path, '/');
            return $trimmed === '' ? '' : '/' . $trimmed;
        }, $paths)));
    }

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function dispatch(Request $request): void
    {
        if ($request->method() === 'OPTIONS') {
            Response::json(['success' => true, 'message' => 'OK', 'data' => []]);
        }

        foreach ($this->routes as $route) {
            $matches = [];
            if ($route['method'] === $request->method() && preg_match($route['pattern'], $request->path(), $matches)) {
                array_shift($matches);
                call_user_func_array($route['handler'], array_merge([$request], $matches));
                return;
            }
        }

        Response::error('Endpoint not found', [], 404);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        foreach ($this->basePaths as $basePath) {
            $fullPath = rtrim($basePath, '/') . '/' . trim($path, '/');
            $pattern = '#^' . preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $fullPath) . '$#';
            $this->routes[] = [
                'method' => $method,
                'pattern' => $pattern,
                'handler' => $handler,
            ];
        }
    }
}

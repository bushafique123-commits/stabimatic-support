<?php

final class Request
{
    private $method;
    private $path;
    private $headers;
    private $input;
    private $query;
    private $files;

    private function __construct(string $method, string $path, array $headers, array $input, array $query, array $files)
    {
        $this->method = $method;
        $this->path = $path;
        $this->headers = $headers;
        $this->input = $input;
        $this->query = $query;
        $this->files = $files;
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $path = '/' . trim(preg_replace('#^' . preg_quote($script, '#') . '#', '', $uri), '/');
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);
        $input = is_array($json) ? $json : $_POST;

        return new self($method, $path, self::headers(), $input, $_GET, $_FILES);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function input(): array
    {
        return $this->input;
    }

    public function query(): array
    {
        return $this->query;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function header(string $name): ?string
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? null;
    }

    private static function headers(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }
        return $headers;
    }
}

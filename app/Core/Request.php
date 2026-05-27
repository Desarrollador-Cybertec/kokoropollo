<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return '/' . trim($path ?? '/', '/');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function json(): mixed
    {
        $body = file_get_contents('php://input');
        return json_decode($body, associative: true);
    }

    public function csrfToken(): string
    {
        return $this->post('_csrf') ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }

    public function all(): array
    {
        return $_POST;
    }
}

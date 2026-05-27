<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $url, int $code = 302): never
    {
        // Only allow internal relative paths — prevents open redirect attacks
        if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
            $url = '/';
        }
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }

    public static function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function notFound(): never
    {
        http_response_code(404);
        echo '404 - Página no encontrada';
        exit;
    }

    public static function forbidden(): never
    {
        http_response_code(403);
        echo '403 - Acceso denegado';
        exit;
    }
}

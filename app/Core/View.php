<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $templatePath = ROOT_PATH . '/resources/views/' . $template . '.php';

        if (!file_exists($templatePath)) {
            http_response_code(500);
            exit("Vista no encontrada: {$template}");
        }

        extract($data, flags: EXTR_SKIP);

        require $templatePath;
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars(
            string: (string) $value,
            flags: ENT_QUOTES | ENT_SUBSTITUTE,
            encoding: 'UTF-8'
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Response, Session};

final class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Session::has('usuario')) {
            Response::redirect('/');
        }
    }
}

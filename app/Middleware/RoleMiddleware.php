<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Response, Session};
use App\Enums\Rol;

final class RoleMiddleware
{
    public static function require(Rol $required): void
    {
        AuthMiddleware::handle();

        $sessionRole = Session::get('rol');
        $actual      = $sessionRole !== null ? Rol::tryFrom($sessionRole) : null;

        if ($actual !== $required) {
            $redirect = $actual?->dashboard() ?? '/';
            Response::redirect($redirect);
        }
    }
}

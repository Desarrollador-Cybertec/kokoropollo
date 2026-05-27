<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, View};
use App\Enums\Rol;
use App\Middleware\{AuthMiddleware, RoleMiddleware};

final class DashboardController
{
    public function admin(Request $request): void
    {
        RoleMiddleware::require(Rol::Administrador);

        View::render('dashboard/admin');
    }

    public function employee(Request $request): void
    {
        RoleMiddleware::require(Rol::Empleado);

        View::render('dashboard/empleado');
    }
}

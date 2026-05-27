<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\Venta;

final class DashboardController
{
    public function index(Request $request): void
    {
        AuthMiddleware::handle();

        $rol      = Rol::tryFrom(Session::get('rol') ?? '');
        $esAdmin  = $rol === Rol::Administrador;
        $totalDia = (new Venta())->sumToday();

        View::render('dashboard/index', compact('esAdmin', 'totalDia'));
    }
}

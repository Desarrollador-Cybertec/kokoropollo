<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, View};
use App\Middleware\AuthMiddleware;
use App\Models\HistorialCaja;

final class HistorialController
{
    public function index(Request $request): void
    {
        AuthMiddleware::handle();

        $desde     = $request->get('desde', '');
        $hasta     = $request->get('hasta', '');
        $registros = (new HistorialCaja())->filter(
            desde: $desde ?: null,
            hasta: $hasta ?: null,
        );

        View::render('historial/index', compact('registros', 'desde', 'hasta'));
    }
}

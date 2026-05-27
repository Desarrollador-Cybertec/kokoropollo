<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Logger, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{Caja, HistorialCaja};

final class CajaController
{
    public function index(Request $request): void
    {
        AuthMiddleware::handle();

        $hoy           = date('Y-m-d');
        $total         = (new Caja())->getTotal();
        $movimientosHoy = (new HistorialCaja())->filter($hoy, $hoy);
        $rol           = Rol::tryFrom(Session::get('rol') ?? '');
        $dashboardUrl  = $rol?->dashboard() ?? '/dashboard';

        View::render('caja/index', compact('total', 'movimientosHoy', 'dashboardUrl'));
    }

    public function process(Request $request): void
    {
        AuthMiddleware::handle();

        if (!Csrf::validateToken($request->csrfToken())) {
            Response::redirect('/caja');
        }

        $accion   = $request->post('accion', '');
        $valor    = (float) $request->post('valor', 0);
        $concepto = trim($request->post('concepto', ''));
        $usuario  = Session::get('usuario', '');

        if (!in_array($accion, ['anadir', 'retirar'], strict: true) || $valor <= 0) {
            Response::redirect('/caja');
        }

        $caja  = new Caja();
        $total = $caja->getTotal();

        $tipo = match($accion) {
            'anadir'  => 'ingreso',
            'retirar' => 'retiro',
        };

        if ($tipo === 'retiro' && $valor > $total) {
            $hoy          = date('Y-m-d');
            $rol          = Rol::tryFrom(Session::get('rol') ?? '');
            $dashboardUrl = $rol?->dashboard() ?? '/dashboard';
            View::render('caja/index', [
                'total'          => $total,
                'movimientosHoy' => (new HistorialCaja())->filter($hoy, $hoy),
                'dashboardUrl'   => $dashboardUrl,
                'error'          => 'No puede retirar más de lo que hay en caja.',
            ]);
            return;
        }

        $nuevoTotal = $tipo === 'ingreso' ? $total + $valor : $total - $valor;
        $caja->updateTotal($nuevoTotal);
        (new HistorialCaja())->create($tipo, $valor, $concepto, $usuario);

        Logger::getInstance()->info("Movimiento de caja: {$tipo}", [
            'valor'   => $valor,
            'usuario' => $usuario,
        ]);

        Response::redirect('/caja');
    }
}

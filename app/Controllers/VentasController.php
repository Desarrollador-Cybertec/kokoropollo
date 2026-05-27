<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Logger, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{Inventario, Venta};

final class VentasController
{
    public function index(Request $request): void
    {
        AuthMiddleware::handle();

        $productos    = (new Inventario())->forSelect();
        $totalDia     = (new Venta())->sumToday();
        $rol          = Rol::tryFrom(Session::get('rol') ?? '');
        $dashboardUrl = $rol?->dashboard() ?? '/dashboard';

        View::render('ventas/index', compact('productos', 'totalDia', 'dashboardUrl'));
    }

    public function store(Request $request): void
    {
        AuthMiddleware::handle();
        header('Content-Type: application/json; charset=utf-8');

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Response::json(['status' => 'error', 'mensaje' => 'Token de seguridad inválido.'], code: 403);
        }

        $data           = $request->json() ?? [];
        $ordenId        = substr(preg_replace('/[^a-zA-Z0-9]/', '', (string) ($data['orden_id'] ?? '')), 0, 12);
        $inventarioId   = (int) ($data['inventario_id']   ?? 0);
        $cantidad       = (int) ($data['cantidad']         ?? 0);
        $precioUnitario = (float) ($data['precio_unitario'] ?? 0);

        if ($inventarioId <= 0 || $cantidad <= 0 || $precioUnitario <= 0) {
            Response::json(['status' => 'error', 'mensaje' => 'Datos inválidos.'], code: 422);
        }

        $total   = $precioUnitario * $cantidad;
        $usuario = Session::get('usuario', '');

        try {
            $ventaId = (new Venta())->store(
                ordenId:        $ordenId,
                inventarioId:   $inventarioId,
                cantidad:       $cantidad,
                precioUnitario: $precioUnitario,
                total:          $total,
                usuario:        $usuario,
            );

            Logger::getInstance()->info('Venta registrada', [
                'id'      => $ventaId,
                'total'   => $total,
                'usuario' => $usuario,
            ]);

            Response::json(['status' => 'ok', 'id' => $ventaId, 'total' => $total]);
        } catch (\RuntimeException $e) {
            Response::json(['status' => 'error', 'mensaje' => $e->getMessage()], code: 422);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Request, Response, Session};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{Caja, RetiroSeguridad};

final class AlsesController
{
    public function store(Request $request): void
    {
        AuthMiddleware::handle();
        $this->soloAdmin();

        header('Content-Type: application/json; charset=utf-8');
        if (!Csrf::validateToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            Response::json(['status' => 'error', 'mensaje' => 'Token inválido.'], 403);
        }

        $data      = $request->json() ?? [];
        $valor     = (float) ($data['valor']  ?? 0);
        $motivo    = substr(trim((string)($data['motivo'] ?? '')), 0, 255);
        $usuarioId = (int) Session::get('usuario_id', 0);

        if ($valor <= 0 || $motivo === '') {
            Response::json(['status' => 'error', 'mensaje' => 'Valor y motivo son obligatorios.'], 422);
        }

        try {
            (new RetiroSeguridad())->crear($valor, $motivo, $usuarioId);
            $nuevoTotal = (new Caja())->getTotal();
            Response::json(['status' => 'ok', 'nuevoCajaTotal' => $nuevoTotal]);
        } catch (\RuntimeException $e) {
            Response::json(['status' => 'error', 'mensaje' => $e->getMessage()], 422);
        }
    }

    private function soloAdmin(): void
    {
        if (Session::get('rol') !== Rol::Administrador->value) {
            Response::json(['status' => 'error', 'mensaje' => 'Acceso denegado.'], 403);
        }
    }
}

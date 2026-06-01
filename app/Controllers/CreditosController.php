<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{CreditoEmpleado, Usuarios};

final class CreditosController
{
    public function index(Request $request): void
    {
        AuthMiddleware::handle();
        $this->soloAdmin();

        $modelo = new CreditoEmpleado();
        $modelo->actualizarVencidos();

        $creditos   = $modelo->all();
        $resumen    = $modelo->resumen();
        $empleados  = $this->getEmpleados();
        $rol        = Rol::tryFrom(Session::get('rol') ?? '');

        View::render('creditos/index', [
            'creditos'     => $creditos,
            'resumen'      => $resumen,
            'empleados'    => $empleados,
            'dashboardUrl' => $rol?->dashboard() ?? '/dashboard',
            'pageTitle'    => 'Créditos Empleados — Kokoro Pollo',
        ]);
    }

    public function crear(Request $request): void
    {
        AuthMiddleware::handle();
        $this->soloAdmin();

        header('Content-Type: application/json; charset=utf-8');
        if (!Csrf::validateToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            Response::json(['status' => 'error', 'mensaje' => 'Token inválido.'], 403);
        }

        $data             = $request->json() ?? [];
        $empleadoId       = (int)   ($data['empleado_id']       ?? 0);
        $valor            = (float) ($data['valor']             ?? 0);
        $fechaPrestamo    = (string)($data['fecha_prestamo']    ?? date('Y-m-d'));
        $fechaCompromiso  = (string)($data['fecha_compromiso']  ?? '');
        $observaciones    = substr(trim((string)($data['observaciones'] ?? '')), 0, 500);
        $creadorId        = (int) Session::get('usuario_id', 0);

        if ($empleadoId <= 0 || $valor <= 0 || !$this->validDate($fechaCompromiso)) {
            Response::json(['status' => 'error', 'mensaje' => 'Datos incompletos o inválidos.'], 422);
        }

        try {
            (new CreditoEmpleado())->crear(
                $empleadoId, $valor, $fechaPrestamo, $fechaCompromiso, $observaciones, $creadorId
            );
            Response::json(['status' => 'ok', 'mensaje' => 'Crédito registrado correctamente.']);
        } catch (\RuntimeException $e) {
            Response::json(['status' => 'error', 'mensaje' => $e->getMessage()], 422);
        }
    }

    public function pagar(Request $request): void
    {
        AuthMiddleware::handle();
        $this->soloAdmin();

        header('Content-Type: application/json; charset=utf-8');
        if (!Csrf::validateToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            Response::json(['status' => 'error', 'mensaje' => 'Token inválido.'], 403);
        }

        $data      = $request->json() ?? [];
        $id        = (int) ($data['id'] ?? 0);
        $usuarioId = (int) Session::get('usuario_id', 0);

        if ($id <= 0) {
            Response::json(['status' => 'error', 'mensaje' => 'ID inválido.'], 422);
        }

        try {
            (new CreditoEmpleado())->pagar($id, $usuarioId);
            Response::json(['status' => 'ok', 'mensaje' => 'Pago registrado correctamente.']);
        } catch (\RuntimeException $e) {
            Response::json(['status' => 'error', 'mensaje' => $e->getMessage()], 422);
        }
    }

    public function vencer(Request $request): void
    {
        AuthMiddleware::handle();
        $this->soloAdmin();

        header('Content-Type: application/json; charset=utf-8');
        if (!Csrf::validateToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            Response::json(['status' => 'error', 'mensaje' => 'Token inválido.'], 403);
        }

        $id = (int) (($request->json() ?? [])['id'] ?? 0);
        if ($id > 0) (new CreditoEmpleado())->vencer($id);
        Response::json(['status' => 'ok']);
    }

    // ── helpers ─────────────────────────────────────────────────

    private function getEmpleados(): array
    {
        $stmt = \App\Core\Database::getInstance()->prepare(
            "SELECT id, nombre, usuario FROM usuarios WHERE rol = 'Empleado' ORDER BY nombre"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function soloAdmin(): void
    {
        if (Session::get('rol') !== Rol::Administrador->value) {
            Response::redirect('/dashboard');
        }
    }

    private function validDate(string $d): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $d);
        return $dt !== false && $dt->format('Y-m-d') === $d;
    }
}

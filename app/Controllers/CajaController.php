<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Logger, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{Caja, HistorialCaja, Venta};

final class CajaController
{
    public function index(Request $request): void
    {
        AuthMiddleware::handle();

        $hoy    = date('Y-m-d');
        $ayer   = date('Y-m-d', strtotime('-1 day'));
        $lunEs  = date('Y-m-d', strtotime('monday this week'));
        $priMes = date('Y-m-01');

        $historialCaja  = new HistorialCaja();
        $total          = (new Caja())->getTotal();
        $movimientosHoy = $historialCaja->filterUnifiedToday($hoy);
        $rol            = Rol::tryFrom(Session::get('rol') ?? '');
        $dashboardUrl   = $rol?->dashboard() ?? '/dashboard';

        $ingresosHoy         = 0.0;
        $retirosHoy          = 0.0;
        $ventasPendientesHoy = (new Venta())->sumPendingLiquidation();

        foreach ($movimientosHoy as $m) {
            if ($m['origen'] !== 'caja') continue;
            if ($m['tipo'] === 'ingreso') $ingresosHoy += (float) $m['valor'];
            if ($m['tipo'] === 'retiro')  $retirosHoy  += (float) $m['valor'];
        }

        View::render('caja/index', compact(
            'total', 'movimientosHoy', 'dashboardUrl',
            'hoy', 'ayer', 'lunEs', 'priMes',
            'ingresosHoy', 'retirosHoy', 'ventasPendientesHoy'
        ));
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
            Session::flash('error', 'No puede retirar más de lo que hay en caja.');
            Response::redirect('/caja');
        }

        $nuevoTotal = $tipo === 'ingreso' ? $total + $valor : $total - $valor;
        $caja->updateTotal($nuevoTotal);
        (new HistorialCaja())->create($tipo, $valor, $concepto, $usuario);

        Logger::getInstance()->info("Movimiento de caja: {$tipo}", [
            'valor'   => $valor,
            'usuario' => $usuario,
        ]);

        $label = $tipo === 'ingreso' ? 'Ingreso' : 'Retiro';
        Session::flash('exito', "{$label} de \${$this->fmt($valor)} registrado.");
        Response::redirect('/caja');
    }

    public function resumen(Request $request): void
    {
        AuthMiddleware::handle();
        header('Content-Type: application/json; charset=utf-8');

        $hoy            = date('Y-m-d');
        $movimientos    = (new HistorialCaja())->filterUnifiedToday($hoy);
        $total          = (new Caja())->getTotal();
        $ventasPendientes = (new Venta())->sumPendingLiquidation();

        $ingresosHoy = 0.0;
        $retirosHoy  = 0.0;
        foreach ($movimientos as $m) {
            if ($m['origen'] !== 'caja') continue;
            if ($m['tipo'] === 'ingreso') $ingresosHoy += (float) $m['valor'];
            if ($m['tipo'] === 'retiro')  $retirosHoy  += (float) $m['valor'];
        }

        Response::json([
            'total'            => $total,
            'ingresosHoy'      => $ingresosHoy,
            'retirosHoy'       => $retirosHoy,
            'ventasPendientes' => $ventasPendientes,
            'movimientos'      => $movimientos,
        ]);
    }

    public function ajuste(Request $request): void
    {
        AuthMiddleware::handle();
        header('Content-Type: application/json; charset=utf-8');

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Response::json(['status' => 'error', 'mensaje' => 'Token de seguridad inválido.'], code: 403);
        }

        $data     = $request->json() ?? [];
        $accion   = (string) ($data['accion']   ?? '');
        $valor    = (float) ($data['valor']      ?? 0);
        $concepto = trim((string) ($data['concepto'] ?? ''));
        $usuario  = Session::get('usuario', '');

        if (!in_array($accion, ['anadir', 'retirar'], strict: true) || $valor <= 0) {
            Response::json(['status' => 'error', 'mensaje' => 'Datos inválidos.'], code: 422);
        }

        $caja  = new Caja();
        $total = $caja->getTotal();

        $tipo = match($accion) {
            'anadir'  => 'ingreso',
            'retirar' => 'retiro',
        };

        if ($tipo === 'retiro' && $valor > $total) {
            Response::json(['status' => 'error', 'mensaje' => 'No puede retirar más de lo que hay en caja.'], code: 422);
        }

        $nuevoTotal = $tipo === 'ingreso' ? $total + $valor : $total - $valor;
        $caja->updateTotal($nuevoTotal);
        (new HistorialCaja())->create($tipo, $valor, $concepto ?: ($tipo === 'ingreso' ? 'Ingreso manual' : 'Retiro manual'), $usuario);

        Logger::getInstance()->info("Ajuste de caja: {$tipo}", [
            'valor'   => $valor,
            'usuario' => $usuario,
        ]);

        Response::json(['status' => 'ok', 'nuevoCajaTotal' => $nuevoTotal, 'tipo' => $tipo, 'valor' => $valor]);
    }

    private function fmt(float $valor): string
    {
        return number_format($valor, 0, ',', '.');
    }
}

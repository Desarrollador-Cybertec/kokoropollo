<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Logger, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{Auditoria, Caja, CajaApertura, CajaCierre, HistorialCaja, Venta};

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

        $esAdmin     = $rol?->atLeast(Rol::Administrador) ?? false;
        $aperturaHoy = $esAdmin ? (new CajaApertura())->getHoy() : null;
        $cierreHoy   = $esAdmin ? (new CajaCierre())->getHoy()   : null;
        $precalc     = ($esAdmin && $aperturaHoy && !$cierreHoy)
            ? (new CajaCierre())->precalcularDia((int) $aperturaHoy['id'])
            : [];
        $denominaciones = CajaApertura::DENOMINACIONES;

        View::render('caja/index', compact(
            'total', 'movimientosHoy', 'dashboardUrl',
            'hoy', 'ayer', 'lunEs', 'priMes',
            'ingresosHoy', 'retirosHoy', 'ventasPendientesHoy',
            'esAdmin', 'aperturaHoy', 'cierreHoy', 'precalc', 'denominaciones'
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

        try {
            $nuevoTotal = $tipo === 'ingreso' ? $total + $valor : $total - $valor;
            $caja->updateTotal($nuevoTotal);
            (new HistorialCaja())->create($tipo, $valor, $concepto, $usuario);
            (new Auditoria())->registrar($usuario, 'caja', 'ajuste',
                ($tipo === 'ingreso' ? 'Ingreso' : 'Retiro') . ' $' . number_format($valor, 0, ',', '.') .
                ($concepto !== '' ? " — {$concepto}" : '')
            );

            Logger::getInstance()->info("Movimiento de caja: {$tipo}", [
                'valor'   => $valor,
                'usuario' => $usuario,
            ]);

            $label = $tipo === 'ingreso' ? 'Ingreso' : 'Retiro';
            Session::flash('exito', "{$label} de \${$this->fmt($valor)} registrado.");
        } catch (\Throwable $e) {
            Logger::getInstance()->error('Error al procesar movimiento de caja', ['error' => $e->getMessage()]);
            Session::flash('error', 'Error al procesar el movimiento. Intente de nuevo.');
        }
        Response::redirect('/caja');
    }

    public function resumen(Request $request): void
    {
        AuthMiddleware::handle();
        // C-03: Solo Admin puede consultar el resumen financiero de caja
        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        if (!$rol?->atLeast(Rol::Administrador)) {
            Response::json(['status' => 'error', 'mensaje' => 'Acceso denegado.'], 403);
        }
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
        // C-03: Solo Administrador o superior puede hacer ajustes de caja
        $rolActual = Rol::tryFrom(Session::get('rol') ?? '');
        if (!$rolActual?->atLeast(Rol::Administrador)) {
            Response::json(['status' => 'error', 'mensaje' => 'Acceso denegado.'], 403);
        }

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

        $tipo = match($accion) {
            'anadir'  => 'ingreso',
            'retirar' => 'retiro',
        };

        try {
            // C-04: ajustar() es atómico con FOR UPDATE — previene race conditions
            $nuevoTotal = (new Caja())->ajustar($tipo, $valor);

            (new HistorialCaja())->create($tipo, $valor, $concepto ?: ($tipo === 'ingreso' ? 'Ingreso manual' : 'Retiro manual'), $usuario);

            Logger::getInstance()->info("Ajuste de caja: {$tipo}", [
                'valor'   => $valor,
                'usuario' => $usuario,
            ]);

            Response::json(['status' => 'ok', 'nuevoCajaTotal' => $nuevoTotal, 'tipo' => $tipo, 'valor' => $valor]);
        } catch (\RuntimeException $e) {
            Response::json(['status' => 'error', 'mensaje' => $e->getMessage()], code: 422);
        } catch (\Throwable $e) {
            Logger::getInstance()->error('Error inesperado en ajuste de caja', ['error' => $e->getMessage()]);
            Response::json(['status' => 'error', 'mensaje' => 'Error al procesar el ajuste. Intente de nuevo.'], code: 500);
        }
    }

    private function fmt(float $valor): string
    {
        return number_format($valor, 0, ',', '.');
    }
}

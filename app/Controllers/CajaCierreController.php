<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{Auditoria, CajaApertura, CajaCierre};

final class CajaCierreController
{
    public function index(Request $request): void
    {
        AuthMiddleware::handle();
        $this->soloAdmin();

        $apertura = (new CajaApertura())->getHoy();
        $cierre   = (new CajaCierre())->getHoy();
        $rol      = Rol::tryFrom(Session::get('rol') ?? '');

        // Sin apertura del día no se puede cerrar
        if (!$apertura && !$cierre) {
            Session::flash('error', 'Debe registrar la apertura de caja antes de cerrar.');
            Response::redirect('/caja/apertura');
        }

        $precalc = (!$cierre && $apertura)
            ? (new CajaCierre())->precalcularDia((int) $apertura['id'])
            : [];

        View::render('caja/cierre', [
            'apertura'       => $apertura,
            'cierre'         => $cierre,
            'precalc'        => $precalc,
            'denominaciones' => CajaApertura::DENOMINACIONES,
            'dashboardUrl'   => $rol?->dashboard() ?? '/dashboard',
            'pageTitle'      => 'Cierre de Caja — Kokoro Pollo',
        ]);
    }

    public function store(Request $request): void
    {
        AuthMiddleware::handle();
        $this->soloAdmin();

        if (!Csrf::validateToken($request->csrfToken())) {
            Session::flash('error', 'Token de seguridad inválido.');
            Response::redirect('/caja');
        }

        $apertura = (new CajaApertura())->getHoy();
        if (!$apertura) {
            Session::flash('error', 'No existe apertura de caja para hoy.');
            Response::redirect('/caja/apertura');
        }

        $modelo = new CajaCierre();
        if ($modelo->existeHoy()) {
            Session::flash('error', 'Ya existe un cierre registrado para hoy.');
            Response::redirect('/caja');
        }

        $usuarioId         = (int) Session::get('usuario_id', 0);
        $aperturaId        = (int) $apertura['id'];
        $observaciones     = trim($request->post('observaciones', ''));

        // C-10: Recalcular montos auditables desde BD — no confiar en valores POST
        // Un admin malintencionado podría manipular ventas/gastos en el formulario
        $precalc           = (new CajaCierre())->precalcularDia($aperturaId);
        $ventas            = $precalc['ventas'];
        $gastosCaja        = $precalc['gastos_caja'];
        $creditosEmpleados = $precalc['creditos_empleados'];
        $alses             = $precalc['alses'];

        // Estas dos no tienen fuente de verdad en BD: vienen del POST
        $otrasEntradas = max(0.0, (float) $request->post('otras_entradas', 0));
        $otrasSalidas  = max(0.0, (float) $request->post('otras_salidas',  0));

        $denominaciones = [];
        foreach (CajaApertura::DENOMINACIONES as $valor) {
            $denominaciones[$valor] = max(0, (int) $request->post("den_{$valor}", 0));
        }

        try {
            $cierreId = $modelo->crear(
                $usuarioId, $aperturaId,
                $ventas, $otrasEntradas, $gastosCaja,
                $creditosEmpleados, $alses, $otrasSalidas,
                $observaciones, $denominaciones
            );
            $cierre = $modelo->getHoy();
            $resultado = $cierre ? (
                ($cierre['sobrante'] > 0
                    ? 'sobrante $' . number_format((float)$cierre['sobrante'], 0, ',', '.')
                    : ($cierre['faltante'] > 0
                        ? 'faltante $' . number_format((float)$cierre['faltante'], 0, ',', '.')
                        : 'cuadre exacto'))
            ) : '';
            (new Auditoria())->registrar(
                Session::get('usuario', ''), 'cierre', 'crear',
                "Cierre registrado — {$resultado}"
            );
            Session::flash('exito', 'Cierre de caja registrado correctamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al registrar el cierre. Intenta de nuevo.');
        }

        Response::redirect('/caja');
    }

    private function soloAdmin(): void
    {
        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        if ($rol === null || !$rol->atLeast(Rol::Administrador)) {
            Response::redirect($rol?->dashboard() ?? '/');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{CajaApertura, CajaCierre};

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
            Response::redirect('/caja/cierre');
        }

        $apertura = (new CajaApertura())->getHoy();
        if (!$apertura) {
            Session::flash('error', 'No existe apertura de caja para hoy.');
            Response::redirect('/caja/apertura');
        }

        $modelo = new CajaCierre();
        if ($modelo->existeHoy()) {
            Session::flash('error', 'Ya existe un cierre registrado para hoy.');
            Response::redirect('/caja/cierre');
        }

        $usuarioId         = (int) Session::get('usuario_id', 0);
        $aperturaId        = (int) $apertura['id'];
        $ventas            = max(0.0, (float) $request->post('ventas', 0));
        $otrasEntradas     = max(0.0, (float) $request->post('otras_entradas', 0));
        $gastosCaja        = max(0.0, (float) $request->post('gastos_caja', 0));
        $creditosEmpleados = max(0.0, (float) $request->post('creditos_empleados', 0));
        $alses             = max(0.0, (float) $request->post('alses', 0));
        $otrasSalidas      = max(0.0, (float) $request->post('otras_salidas', 0));
        $observaciones     = trim($request->post('observaciones', ''));

        $denominaciones = [];
        foreach (CajaApertura::DENOMINACIONES as $valor) {
            $denominaciones[$valor] = max(0, (int) $request->post("den_{$valor}", 0));
        }

        try {
            $modelo->crear(
                $usuarioId, $aperturaId,
                $ventas, $otrasEntradas, $gastosCaja,
                $creditosEmpleados, $alses, $otrasSalidas,
                $observaciones, $denominaciones
            );
            Session::flash('exito', 'Cierre de caja registrado correctamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al registrar el cierre. Intenta de nuevo.');
        }

        Response::redirect('/caja/cierre');
    }

    private function soloAdmin(): void
    {
        if (Session::get('rol') !== Rol::Administrador->value) {
            Response::redirect('/dashboard');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\CajaApertura;

final class CajaAperturaController
{
    public function index(Request $request): void
    {
        AuthMiddleware::handle();
        $this->soloAdmin();

        $apertura = (new CajaApertura())->getHoy();
        $rol      = Rol::tryFrom(Session::get('rol') ?? '');

        View::render('caja/apertura', [
            'apertura'      => $apertura,
            'denominaciones' => CajaApertura::DENOMINACIONES,
            'dashboardUrl'  => $rol?->dashboard() ?? '/dashboard',
            'pageTitle'     => 'Apertura de Caja — Kokoro Pollo',
        ]);
    }

    public function store(Request $request): void
    {
        AuthMiddleware::handle();
        $this->soloAdmin();

        if (!Csrf::validateToken($request->csrfToken())) {
            Session::flash('error', 'Token de seguridad inválido.');
            Response::redirect('/caja/apertura');
        }

        $model = new CajaApertura();

        if ($model->existeHoy()) {
            Session::flash('error', 'Ya existe una apertura registrada para hoy.');
            Response::redirect('/caja/apertura');
        }

        $usuarioId    = (int) Session::get('usuario_id', 0);
        $observaciones = trim($request->post('observaciones', ''));

        // Construir mapa [denominacion => cantidad] desde el POST
        $denominaciones = [];
        foreach (CajaApertura::DENOMINACIONES as $valor) {
            $denominaciones[$valor] = max(0, (int) $request->post("den_{$valor}", 0));
        }

        try {
            $model->crear($usuarioId, $observaciones, $denominaciones);
            Session::flash('exito', 'Caja abierta correctamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al registrar la apertura. Intenta de nuevo.');
        }

        Response::redirect('/caja/apertura');
    }

    private function soloAdmin(): void
    {
        if (Session::get('rol') !== Rol::Administrador->value) {
            Response::redirect('/dashboard');
        }
    }
}

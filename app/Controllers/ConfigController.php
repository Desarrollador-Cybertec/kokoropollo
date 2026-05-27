<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\RoleMiddleware;
use App\Models\Configuracion;

final class ConfigController
{
    private const PRECIOS_KEYS = [
        'precio_asado_cuarto', 'precio_asado_medio', 'precio_asado_entero',
        'precio_broaster_cuarto', 'precio_broaster_medio', 'precio_broaster_entero',
    ];

    public function index(Request $request): void
    {
        RoleMiddleware::require(Rol::Administrador);

        $precios = (new Configuracion())->getMany(self::PRECIOS_KEYS);
        View::render('config/index', compact('precios'));
    }

    public function save(Request $request): void
    {
        RoleMiddleware::require(Rol::Administrador);

        if (!Csrf::validateToken($request->csrfToken())) {
            Response::redirect('/config');
        }

        $datos = [];
        foreach (self::PRECIOS_KEYS as $clave) {
            $valor = max(0.0, (float) $request->post($clave, '0'));
            $datos[$clave] = number_format($valor, 2, '.', '');
        }

        (new Configuracion())->setMany($datos);
        Session::flash('exito', 'Precios actualizados correctamente.');
        Response::redirect('/config');
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\RoleMiddleware;
use App\Models\{Configuracion, Venta};

final class ConfigController
{
    private const PRECIOS_KEYS = [
        'precio_asado_cuarto', 'precio_asado_medio', 'precio_asado_entero',
    ];

    public function index(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        $cfg             = new Configuracion();
        $precios         = $cfg->getMany(self::PRECIOS_KEYS);
        $cuartosTotal    = (new Venta())->sumCuartosPolloVendidos();
        $cuartosOffset   = (int) $cfg->get('condimentos_cuartos_offset', '0');
        $pollosPorCiclo  = max(1, (int) $cfg->get('condimentos_pollos_por_ciclo', '1000'));
        $pollosEnCiclo   = (int) floor(max(0, $cuartosTotal - $cuartosOffset) / 4);
        $pctCondimentos  = min(100, round($pollosEnCiclo / $pollosPorCiclo * 100, 1));

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        View::render('config/index', compact(
            'precios', 'pollosEnCiclo', 'pollosPorCiclo', 'pctCondimentos', 'cuartosTotal'
        ) + ['dashboardUrl' => $rol?->dashboard() ?? '/dashboard']);
    }

    public function save(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        if (!Csrf::validateToken($request->csrfToken())) {
            Response::redirect('/config');
        }

        $datos = [];
        foreach (self::PRECIOS_KEYS as $clave) {
            $valor = max(0.0, (float) $request->post($clave, '0'));
            $datos[$clave] = number_format($valor, 2, '.', '');
        }

        // Capacidad del ciclo de condimentos
        $ciclo = max(1, (int) $request->post('condimentos_pollos_por_ciclo', '1000'));
        $datos['condimentos_pollos_por_ciclo'] = (string) $ciclo;

        (new Configuracion())->setMany($datos);
        Session::flash('exito', 'Configuración actualizada correctamente.');
        Response::redirect('/config');
    }

    public function resetCondimentos(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        if (!Csrf::validateToken($request->csrfToken())) {
            Response::redirect('/config');
        }

        // El nuevo offset = cuartos vendidos actualmente → ciclo empieza en 0
        $cuartosActuales = (new Venta())->sumCuartosPolloVendidos();
        (new Configuracion())->setMany(['condimentos_cuartos_offset' => (string) $cuartosActuales]);

        Session::flash('exito', 'Ciclo de condimentos reiniciado. Contador en 0 pollos.');
        Response::redirect('/config');
    }
}

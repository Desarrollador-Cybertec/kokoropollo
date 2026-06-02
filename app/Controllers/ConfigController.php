<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\RoleMiddleware;
use App\Models\{Auditoria, Configuracion, Inventario, Venta};

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

        $empaqueActivo       = $cfg->get('empaque_activo', '0');
        $empaqueInventarioId = $cfg->get('empaque_inventario_id', '0');
        $inventarioItems     = (new Inventario())->all();

        $porcionCfg = $cfg->getMany([
            'porcion_papa_activa',     'porcion_papa_precio',
            'porcion_francesa_activa', 'porcion_francesa_precio',
            'porcion_maduro_activa',   'porcion_maduro_precio',
        ]);
        // Los IDs de porciones ya no se usan (porciones son virtuales desde migración 010)

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        View::render('config/index', compact(
            'precios', 'pollosEnCiclo', 'pollosPorCiclo', 'pctCondimentos', 'cuartosTotal',
            'empaqueActivo', 'empaqueInventarioId', 'inventarioItems', 'porcionCfg'
        ) + ['dashboardUrl' => $rol?->dashboard() ?? '/dashboard']);
    }

    public function save(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        if (!Csrf::validateToken($request->csrfToken())) {
            Response::redirect('/config');
        }

        $cfg       = new Configuracion();
        $oldPrecios = $cfg->getMany(self::PRECIOS_KEYS);

        $datos = [];
        foreach (self::PRECIOS_KEYS as $clave) {
            $valor = max(0.0, (float) $request->post($clave, '0'));
            $datos[$clave] = number_format($valor, 2, '.', '');
        }

        // Capacidad del ciclo de condimentos
        $ciclo = max(1, (int) $request->post('condimentos_pollos_por_ciclo', '1000'));
        $datos['condimentos_pollos_por_ciclo'] = (string) $ciclo;

        // Empaque automático para pedidos para llevar
        $datos['empaque_activo']        = $request->post('empaque_activo', '0') === '1' ? '1' : '0';
        $datos['empaque_inventario_id'] = (string) max(0, (int) $request->post('empaque_inventario_id', '0'));

        // Porciones especiales
        foreach (['papa', 'francesa', 'maduro'] as $k) {
            $datos["porcion_{$k}_activa"] = $request->post("porcion_{$k}_activa", '0') === '1' ? '1' : '0';
            $datos["porcion_{$k}_precio"] = (string) max(0, (float) $request->post("porcion_{$k}_precio", '0'));
        }

        try {
            $cfg->setMany($datos);

            // Registrar en auditoría los precios que cambiaron
            $usuario   = Session::get('usuario', '');
            $auditoria = new Auditoria();
            $etiquetas = [
                'precio_asado_cuarto' => '¼ Asado',
                'precio_asado_medio'  => '½ Asado',
                'precio_asado_entero' => 'Entero Asado',
            ];
            foreach (self::PRECIOS_KEYS as $clave) {
                $viejo = (float) ($oldPrecios[$clave] ?? 0);
                $nuevo = (float) $datos[$clave];
                if (abs($viejo - $nuevo) > 0.01) {
                    $label = $etiquetas[$clave] ?? $clave;
                    $auditoria->registrar($usuario, 'config', 'editar',
                        "Precio {$label}: $" . number_format($viejo, 0, ',', '.') .
                        " → $" . number_format($nuevo, 0, ',', '.')
                    );
                }
            }
            Session::flash('exito', 'Configuración actualizada correctamente.');
        } catch (\Throwable $e) {
            \App\Core\Logger::getInstance()->error('Error al guardar configuración', [
                'error' => $e->getMessage(),
                'usuario' => Session::get('usuario', ''),
            ]);
            Session::flash('error', 'Error al guardar la configuración. Intente de nuevo.');
        }
        Response::redirect('/config');
    }

    public function historialPrecios(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        $pagina    = max(1, (int) $request->get('pagina', '1'));
        $auditoria = new Auditoria();
        $registros = $auditoria->filtrar($pagina, 'config');
        $total     = $auditoria->countFiltrar('config');
        $totalPags = (int) ceil($total / Auditoria::porPagina());

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        View::render('config/historial-precios', [
            'registros'    => $registros,
            'total'        => $total,
            'totalPags'    => $totalPags,
            'pagina'       => $pagina,
            'dashboardUrl' => $rol?->dashboard() ?? '/dashboard',
            'pageTitle'    => 'Historial de Precios — Kokoro Pollo',
        ]);
    }

    public function resetCondimentos(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        if (!Csrf::validateToken($request->csrfToken())) {
            Response::redirect('/config');
        }

        try {
            $cuartosActuales = (new Venta())->sumCuartosPolloVendidos();
            (new Configuracion())->setMany(['condimentos_cuartos_offset' => (string) $cuartosActuales]);
            Session::flash('exito', 'Ciclo de condimentos reiniciado. Contador en 0 pollos.');
        } catch (\Throwable $e) {
            \App\Core\Logger::getInstance()->error('Error al reiniciar ciclo condimentos', ['error' => $e->getMessage()]);
            Session::flash('error', 'Error al reiniciar el ciclo. Intente de nuevo.');
        }
        Response::redirect('/config');
    }
}

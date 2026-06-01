<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{Caja, CajaApertura, CajaCierre, Configuracion, CreditoEmpleado, Inventario, Reporte, Venta};

final class DashboardController
{
    public function index(Request $request): void
    {
        AuthMiddleware::handle();

        $rol = Rol::tryFrom(Session::get('rol') ?? '');

        // Jefe tiene su propio dashboard ejecutivo
        if ($rol === Rol::Jefe) {
            Response::redirect('/dashboard/jefe');
        }

        $esAdmin  = $rol?->atLeast(Rol::Administrador) ?? false;
        $totalDia = (new Venta())->sumToday();

        // Alerta de condimentos
        $cfg            = new Configuracion();
        $cuartosTotal   = (new Venta())->sumCuartosPolloVendidos();
        $cuartosOffset  = (int) $cfg->get('condimentos_cuartos_offset', '0');
        $pollosPorCiclo = max(1, (int) $cfg->get('condimentos_pollos_por_ciclo', '1000'));
        $pollosEnCiclo  = (int) floor(max(0, $cuartosTotal - $cuartosOffset) / 4);
        $pctCondimentos = min(100, round($pollosEnCiclo / $pollosPorCiclo * 100, 1));

        $alertaCondimentos = match(true) {
            $pctCondimentos >= 100 => 'agotado',
            $pctCondimentos >= 80  => 'critica',
            $pctCondimentos >= 50  => 'preventiva',
            default                => null,
        };

        View::render('dashboard/index', compact(
            'esAdmin', 'totalDia',
            'pollosEnCiclo', 'pollosPorCiclo', 'pctCondimentos', 'alertaCondimentos'
        ));
    }

    public function indexJefe(Request $request): void
    {
        AuthMiddleware::handle();

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        if (!$rol?->atLeast(Rol::Jefe)) {
            Response::redirect($rol?->dashboard() ?? '/');
        }

        $hoy     = date('Y-m-d');
        $reporte = new Reporte();
        $cfg     = new Configuracion();

        // KPIs del día
        $resumenHoy = $reporte->resumenDia($hoy);
        $cajaTotal  = (new Caja())->getTotal();
        $pendiente  = (new Venta())->sumPendingLiquidation();

        // Estado operativo
        $aperturaHoy = (new CajaApertura())->existeHoy();
        $cierreHoy   = (new CajaCierre())->existeHoy();

        // Créditos
        $resumCreditos = (new CreditoEmpleado())->resumen();

        // Stock crítico
        $stockCritico = (new Inventario())->countCritico();

        // Condimentos
        $cuartosTotal   = (new Venta())->sumCuartosPolloVendidos();
        $cuartosOffset  = (int) $cfg->get('condimentos_cuartos_offset', '0');
        $pollosPorCiclo = max(1, (int) $cfg->get('condimentos_pollos_por_ciclo', '1000'));
        $pollosEnCiclo  = (int) floor(max(0, $cuartosTotal - $cuartosOffset) / 4);
        $pctCondimentos = min(100, round($pollosEnCiclo / $pollosPorCiclo * 100, 1));
        $alertaCondimentos = match(true) {
            $pctCondimentos >= 100 => 'agotado',
            $pctCondimentos >= 80  => 'critica',
            $pctCondimentos >= 50  => 'preventiva',
            default                => null,
        };

        // Ventas del mes
        [$mesDesde, $mesHasta] = Reporte::mesActual();
        $resumenMes = $reporte->resumenPeriodo($mesDesde, $mesHasta);

        View::render('dashboard/jefe', compact(
            'resumenHoy', 'cajaTotal', 'pendiente',
            'aperturaHoy', 'cierreHoy',
            'resumCreditos', 'stockCritico',
            'pollosEnCiclo', 'pollosPorCiclo', 'pctCondimentos', 'alertaCondimentos',
            'resumenMes', 'mesDesde', 'mesHasta'
        ));
    }
}

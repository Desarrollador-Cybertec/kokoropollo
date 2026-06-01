<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\RoleMiddleware;
use App\Models\Reporte;

final class ReportesController
{
    public function index(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);
        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        View::render('reportes/index', [
            'dashboardUrl' => $rol?->dashboard() ?? '/dashboard',
            'pageTitle'    => 'Reportes — Kokoro Pollo',
        ]);
    }

    public function diario(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        $fecha = $this->validDate($request->get('fecha', date('Y-m-d')))
            ? $request->get('fecha', date('Y-m-d'))
            : date('Y-m-d');

        $modelo  = new Reporte();
        $resumen = $modelo->resumenDia($fecha);
        $porHora = $modelo->ventasPorHora($fecha);
        $topProd = $modelo->topProductosDia($fecha);

        if ($request->get('export') === 'csv') {
            $cab  = ['Hora', 'Pedidos', 'Ventas ($)'];
            $fils = array_map(fn($r) => [$r['hora'] . ':00', $r['pedidos'], $r['total']], $porHora);
            $this->exportCsv("diario_{$fecha}", $cab, $fils);
        }

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        View::render('reportes/diario', [
            'fecha'        => $fecha,
            'resumen'      => $resumen,
            'porHora'      => $porHora,
            'topProd'      => $topProd,
            'dashboardUrl' => $rol?->dashboard() ?? '/dashboard',
            'pageTitle'    => 'Reporte Diario — Kokoro Pollo',
        ]);
    }

    public function semanal(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        [$defDesde, $defHasta] = Reporte::semanaActual();
        $rawDesde = $request->get('desde', $defDesde);
        $rawHasta = $request->get('hasta', $defHasta);
        $desde = $this->validDate($rawDesde) ? $rawDesde : $defDesde;
        $hasta = $this->validDate($rawHasta) ? $rawHasta : $defHasta;
        if ($hasta < $desde) $hasta = $desde;

        $modelo    = new Reporte();
        $resumen   = $modelo->resumenPeriodo($desde, $hasta);
        $porDia    = $modelo->ventasPorDia($desde, $hasta);
        $topProd   = $modelo->topProductos($desde, $hasta, 10);
        $mejorDia  = !empty($porDia) ? max($porDia, fn($a, $b) => $a['ventas'] <=> $b['ventas']) : null;
        $peorDia   = !empty($porDia) ? min($porDia,  fn($a, $b) => $a['ventas'] <=> $b['ventas']) : null;

        if ($request->get('export') === 'csv') {
            $cab  = ['Fecha', 'Pedidos', 'Ventas ($)'];
            $fils = array_map(fn($r) => [$r['dia'], $r['pedidos'], $r['ventas']], $porDia);
            $this->exportCsv("semanal_{$desde}_{$hasta}", $cab, $fils);
        }

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        View::render('reportes/semanal', [
            'desde'        => $desde,
            'hasta'        => $hasta,
            'resumen'      => $resumen,
            'porDia'       => $porDia,
            'topProd'      => $topProd,
            'mejorDia'     => $mejorDia,
            'peorDia'      => $peorDia,
            'dashboardUrl' => $rol?->dashboard() ?? '/dashboard',
            'pageTitle'    => 'Reporte Semanal — Kokoro Pollo',
            'titulo'       => 'Semanal',
        ]);
    }

    public function mensual(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        [$defDesde, $defHasta] = Reporte::mesActual();
        $mes   = $request->get('mes', date('Y-m'));
        if (preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $desde = $mes . '-01';
            $hasta = date('Y-m-t', strtotime($desde));
        } else {
            [$desde, $hasta] = [$defDesde, $defHasta];
            $mes = date('Y-m');
        }

        $modelo  = new Reporte();
        $resumen = $modelo->resumenPeriodo($desde, $hasta);
        $porDia  = $modelo->ventasPorDia($desde, $hasta);
        $topProd = $modelo->topProductos($desde, $hasta, 10);

        if ($request->get('export') === 'csv') {
            $cab  = ['Fecha', 'Pedidos', 'Ventas ($)'];
            $fils = array_map(fn($r) => [$r['dia'], $r['pedidos'], $r['ventas']], $porDia);
            $this->exportCsv("mensual_{$mes}", $cab, $fils);
        }

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        View::render('reportes/mensual', [
            'mes'          => $mes,
            'desde'        => $desde,
            'hasta'        => $hasta,
            'resumen'      => $resumen,
            'porDia'       => $porDia,
            'topProd'      => $topProd,
            'dashboardUrl' => $rol?->dashboard() ?? '/dashboard',
            'pageTitle'    => 'Reporte Mensual — Kokoro Pollo',
        ]);
    }

    public function productos(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        $rawDesde = $request->get('desde', date('Y-m-01'));
        $rawHasta = $request->get('hasta', date('Y-m-d'));
        $desde = $this->validDate($rawDesde) ? $rawDesde : date('Y-m-01');
        $hasta = $this->validDate($rawHasta) ? $rawHasta : date('Y-m-d');
        if ($hasta < $desde) $hasta = $desde;

        $topProd = (new Reporte())->topProductos($desde, $hasta, 20);

        if ($request->get('export') === 'csv') {
            $cab  = ['#', 'Producto', 'Categoría', 'Pedidos', 'Uds vendidas', 'Ingresos ($)', 'Costo ($)', 'Margen ($)'];
            $fils = array_map(fn($i, $r) => [
                $i + 1, $r['articulo'], $r['categoria'], $r['pedidos'],
                $r['uds_vendidas'], $r['ingresos'], $r['costo'],
                (float)$r['ingresos'] - (float)$r['costo'],
            ], array_keys($topProd), $topProd);
            $this->exportCsv("productos_{$desde}_{$hasta}", $cab, $fils);
        }

        $rol = Rol::tryFrom(Session::get('rol') ?? '');

        View::render('reportes/productos', [
            'desde'        => $desde,
            'hasta'        => $hasta,
            'topProd'      => $topProd,
            'dashboardUrl' => $rol?->dashboard() ?? '/dashboard',
            'pageTitle'    => 'Top Productos — Kokoro Pollo',
        ]);
    }

    public function empleados(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        $rawDesde = $request->get('desde', date('Y-m-01'));
        $rawHasta = $request->get('hasta', date('Y-m-d'));
        $desde    = $this->validDate($rawDesde) ? $rawDesde : date('Y-m-01');
        $hasta    = $this->validDate($rawHasta) ? $rawHasta : date('Y-m-d');
        if ($hasta < $desde) $hasta = $desde;

        $usuario   = trim($request->get('usuario', ''));
        $modelo    = new Reporte();
        $ranking   = $modelo->ventasPorEmpleado($desde, $hasta);
        $porDia    = $usuario !== '' ? $modelo->ventasEmpleadoPorDia($desde, $hasta, $usuario) : [];

        if ($request->get('export') === 'csv') {
            $cab  = ['#', 'Empleado', 'Pedidos', 'Ventas ($)', 'Ticket promedio ($)', 'Local', 'Para llevar', 'Días activo'];
            $fils = array_map(fn($i, $r) => [
                $i + 1, $r['usuario'], $r['pedidos'], $r['ventas'],
                round((float)$r['ticket_promedio']), $r['pedidos_local'], $r['pedidos_llevar'], $r['dias_activo'],
            ], array_keys($ranking), $ranking);
            $this->exportCsv("empleados_{$desde}_{$hasta}", $cab, $fils);
        }

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        View::render('reportes/empleados', [
            'desde'        => $desde,
            'hasta'        => $hasta,
            'usuario'      => $usuario,
            'ranking'      => $ranking,
            'porDia'       => $porDia,
            'dashboardUrl' => $rol?->dashboard() ?? '/dashboard',
            'pageTitle'    => 'Ventas por Empleado — Kokoro Pollo',
        ]);
    }

    // ── CSV Export ─────────────────────────────────────────────

    private function exportCsv(string $nombre, array $cabeceras, array $filas): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
        fputcsv($out, $cabeceras, ';');
        foreach ($filas as $fila) {
            fputcsv($out, $fila, ';');
        }
        fclose($out);
        exit;
    }

    private function validDate(string $d): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $d);
        return $dt !== false && $dt->format('Y-m-d') === $d;
    }
}

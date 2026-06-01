<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Reporte
{
    // ── REPORTE DIARIO ─────────────────────────────────────────

    /** Resumen financiero completo de un día */
    public function resumenDia(string $fecha): array
    {
        $pdo = Database::getInstance();

        // Ventas: total, pedidos, por tipo
        $stmt = $pdo->prepare(
            "SELECT
                COALESCE(SUM(v.total), 0)                                    AS total_ventas,
                COALESCE(SUM(v.cantidad * COALESCE(i.valor, 0)), 0)          AS costo_ventas,
                COUNT(DISTINCT v.orden_id)                                   AS total_pedidos,
                COUNT(DISTINCT CASE WHEN v.tipo_pedido='llevar' THEN v.orden_id END) AS pedidos_llevar,
                COUNT(DISTINCT CASE WHEN v.tipo_pedido='local'  THEN v.orden_id END) AS pedidos_local
             FROM ventas v
             LEFT JOIN inventario i ON i.id = v.inventario_id
             WHERE DATE(v.fecha) = ?"
        );
        $stmt->execute([$fecha]);
        $ventas = $stmt->fetch();

        // Ingresos manuales de caja (excluye liquidaciones)
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM historial_caja
             WHERE DATE(fecha) = ? AND tipo = 'ingreso'
             AND concepto NOT LIKE 'Liquidaci%'
             AND concepto NOT LIKE 'Pago cr%'"
        );
        $stmt->execute([$fecha]);
        $ingresosManuals = (float) $stmt->fetchColumn();

        // Gastos manuales de caja
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM historial_caja
             WHERE DATE(fecha) = ? AND tipo = 'retiro'"
        );
        $stmt->execute([$fecha]);
        $gastos = (float) $stmt->fetchColumn();

        // Créditos entregados
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(valor), 0) FROM creditos_empleados WHERE DATE(created_at) = ?'
        );
        $stmt->execute([$fecha]);
        $creditos = (float) $stmt->fetchColumn();

        // ALSÉS
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(valor), 0) FROM retiros_seguridad WHERE DATE(fecha) = ?'
        );
        $stmt->execute([$fecha]);
        $alses = (float) $stmt->fetchColumn();

        $totalVentas  = (float) ($ventas['total_ventas'] ?? 0);
        $costoVentas  = (float) ($ventas['costo_ventas'] ?? 0);
        $utilidad     = $totalVentas - $costoVentas;

        return [
            'fecha'           => $fecha,
            'total_ventas'    => $totalVentas,
            'costo_ventas'    => $costoVentas,
            'utilidad'        => $utilidad,
            'margen_pct'      => $totalVentas > 0 ? round($utilidad / $totalVentas * 100, 1) : 0,
            'total_pedidos'   => (int) ($ventas['total_pedidos'] ?? 0),
            'pedidos_local'   => (int) ($ventas['pedidos_local'] ?? 0),
            'pedidos_llevar'  => (int) ($ventas['pedidos_llevar'] ?? 0),
            'ingresos_manual' => $ingresosManuals,
            'gastos'          => $gastos,
            'creditos'        => $creditos,
            'alses'           => $alses,
        ];
    }

    /** Ventas por hora del día */
    public function ventasPorHora(string $fecha): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT HOUR(fecha) AS hora,
                    COUNT(DISTINCT orden_id) AS pedidos,
                    COALESCE(SUM(total), 0) AS total
             FROM ventas
             WHERE DATE(fecha) = ?
             GROUP BY HOUR(fecha)
             ORDER BY hora'
        );
        $stmt->execute([$fecha]);
        return $stmt->fetchAll();
    }

    /** Top 10 productos del día */
    public function topProductosDia(string $fecha): array
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT COALESCE(i.articulo, v.item_descripcion, 'Porción') AS articulo,
                    COALESCE(i.categoria, 'Porciones') AS categoria,
                    SUM(v.cantidad)                         AS uds_vendidas,
                    SUM(v.total)                            AS ingresos,
                    SUM(v.cantidad * COALESCE(i.valor, 0))  AS costo
             FROM ventas v
             LEFT JOIN inventario i ON i.id = v.inventario_id
             WHERE DATE(v.fecha) = ?
             GROUP BY v.inventario_id, v.item_descripcion
             ORDER BY ingresos DESC
             LIMIT 10"
        );
        $stmt->execute([$fecha]);
        return $stmt->fetchAll();
    }

    // ── REPORTE SEMANAL ────────────────────────────────────────

    /** Ventas día a día en un rango */
    public function ventasPorDia(string $desde, string $hasta): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT DATE(fecha) AS dia,
                    COUNT(DISTINCT orden_id) AS pedidos,
                    COALESCE(SUM(total), 0)  AS ventas
             FROM ventas
             WHERE fecha BETWEEN ? AND ?
             GROUP BY DATE(fecha)
             ORDER BY dia'
        );
        $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        return $stmt->fetchAll();
    }

    /** Resumen del período (semanal / mensual) */
    public function resumenPeriodo(string $desde, string $hasta): array
    {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare(
            'SELECT
                COALESCE(SUM(v.total), 0)                           AS total_ventas,
                COALESCE(SUM(v.cantidad * COALESCE(i.valor, 0)), 0) AS costo_ventas,
                COUNT(DISTINCT v.orden_id)                          AS total_pedidos,
                COUNT(DISTINCT DATE(v.fecha))                       AS dias_con_ventas
             FROM ventas v
             LEFT JOIN inventario i ON i.id = v.inventario_id
             WHERE v.fecha BETWEEN ? AND ?'
        );
        $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        $ventas = $stmt->fetch();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor),0) FROM historial_caja
             WHERE fecha BETWEEN ? AND ? AND tipo = 'retiro'"
        );
        $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        $gastos = (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor),0) FROM creditos_empleados
             WHERE created_at BETWEEN ? AND ?"
        );
        $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        $creditos = (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor),0) FROM retiros_seguridad
             WHERE fecha BETWEEN ? AND ?"
        );
        $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        $alses = (float) $stmt->fetchColumn();

        $totalVentas = (float) ($ventas['total_ventas'] ?? 0);
        $costoVentas = (float) ($ventas['costo_ventas'] ?? 0);
        $diasConVentas = max(1, (int) ($ventas['dias_con_ventas'] ?? 1));

        return [
            'total_ventas'    => $totalVentas,
            'costo_ventas'    => $costoVentas,
            'utilidad'        => $totalVentas - $costoVentas,
            'margen_pct'      => $totalVentas > 0 ? round(($totalVentas - $costoVentas) / $totalVentas * 100, 1) : 0,
            'total_pedidos'   => (int) ($ventas['total_pedidos'] ?? 0),
            'promedio_dia'    => round($totalVentas / $diasConVentas, 0),
            'dias_con_ventas' => $diasConVentas,
            'gastos'          => $gastos,
            'creditos'        => $creditos,
            'alses'           => $alses,
        ];
    }

    // ── TOP PRODUCTOS (rango libre) ────────────────────────────

    public function topProductos(string $desde, string $hasta, int $limite = 15): array
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT COALESCE(i.articulo, v.item_descripcion, 'Porción') AS articulo,
                    COALESCE(i.categoria, 'Porciones')                  AS categoria,
                    SUM(v.cantidad)                                     AS uds_vendidas,
                    SUM(v.total)                                        AS ingresos,
                    SUM(v.cantidad * COALESCE(i.valor, 0))              AS costo,
                    COUNT(DISTINCT v.orden_id)                          AS pedidos
             FROM ventas v
             LEFT JOIN inventario i ON i.id = v.inventario_id
             WHERE v.fecha BETWEEN ? AND ?
             GROUP BY v.inventario_id, v.item_descripcion
             ORDER BY uds_vendidas DESC
             LIMIT ?"
        );
        $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59', $limite]);
        return $stmt->fetchAll();
    }

    // ── VENTAS POR EMPLEADO ────────────────────────────────────

    /** Ranking de empleados en un rango de fechas */
    public function ventasPorEmpleado(string $desde, string $hasta): array
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT
                v.usuario,
                COUNT(DISTINCT v.orden_id)                                       AS pedidos,
                COALESCE(SUM(v.total), 0)                                        AS ventas,
                COALESCE(SUM(v.total) / NULLIF(COUNT(DISTINCT v.orden_id), 0), 0) AS ticket_promedio,
                COUNT(DISTINCT CASE WHEN v.tipo_pedido='local'  THEN v.orden_id END) AS pedidos_local,
                COUNT(DISTINCT CASE WHEN v.tipo_pedido='llevar' THEN v.orden_id END) AS pedidos_llevar,
                COUNT(DISTINCT DATE(v.fecha))                                    AS dias_activo
             FROM ventas v
             WHERE v.fecha BETWEEN ? AND ?
             GROUP BY v.usuario
             ORDER BY ventas DESC"
        );
        $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        return $stmt->fetchAll();
    }

    /** Ventas día a día de un empleado en particular */
    public function ventasEmpleadoPorDia(string $desde, string $hasta, string $usuario): array
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT
                DATE(v.fecha)            AS dia,
                COUNT(DISTINCT v.orden_id) AS pedidos,
                COALESCE(SUM(v.total), 0)  AS ventas
             FROM ventas v
             WHERE v.fecha BETWEEN ? AND ? AND v.usuario = ?
             GROUP BY DATE(v.fecha)
             ORDER BY dia ASC"
        );
        $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59', $usuario]);
        return $stmt->fetchAll();
    }

    // ── HELPERS ────────────────────────────────────────────────

    /** Semana actual: lunes → domingo */
    public static function semanaActual(): array
    {
        $lunes   = date('Y-m-d', strtotime('monday this week'));
        $domingo = date('Y-m-d', strtotime('sunday this week'));
        return [$lunes, $domingo];
    }

    /** Mes actual: primer y último día */
    public static function mesActual(): array
    {
        return [date('Y-m-01'), date('Y-m-t')];
    }

    /** Quincena actual */
    public static function quincenaActual(): array
    {
        $dia = (int) date('d');
        $mes = date('Y-m');
        if ($dia <= 15) {
            return ["{$mes}-01", "{$mes}-15"];
        }
        return ["{$mes}-16", date('Y-m-t')];
    }
}

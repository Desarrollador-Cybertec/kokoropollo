<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class HistorialCaja
{
    /** @var array<string, bool> */
    private static array $columnCache = [];

    private static function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        $stmt = Database::getInstance()->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);

        self::$columnCache[$key] = (int) $stmt->fetchColumn() > 0;
        return self::$columnCache[$key];
    }

    public function create(string $tipo, float $valor, string $concepto, string $usuario): void
    {
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO historial_caja (tipo, valor, concepto, usuario, fecha)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$tipo, $valor, $concepto, $usuario]);
    }

    public function filter(?string $desde, ?string $hasta): array
    {
        [$where, $params] = $this->buildWhere($desde, $hasta);
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM historial_caja' . $where . ' ORDER BY fecha DESC'
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(?string $desde, ?string $hasta): int
    {
        [$where, $params] = $this->buildWhere($desde, $hasta);
        $stmt = Database::getInstance()->prepare(
            'SELECT COUNT(*) FROM historial_caja' . $where
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function filterPaginated(?string $desde, ?string $hasta, int $pagina = 1, int $porPagina = 50): array
    {
        [$where, $params] = $this->buildWhere($desde, $hasta);
        $offset   = ($pagina - 1) * $porPagina;
        $params[] = $porPagina;
        $params[] = $offset;
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM historial_caja' . $where . ' ORDER BY fecha DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function filterUnifiedToday(string $hoy): array
    {
        $hasItemDescripcion = self::hasColumn('ventas', 'item_descripcion');
        $hasLiquidado = self::hasColumn('ventas', 'liquidado');
        $hasTipoPedido = self::hasColumn('ventas', 'tipo_pedido');
        $hasNombreCliente = self::hasColumn('ventas', 'nombre_cliente');
        $hasDireccion = self::hasColumn('ventas', 'direccion');

        $conceptoExpr = $hasItemDescripcion
            ? 'COALESCE(i.articulo, v.item_descripcion)'
            : 'COALESCE(i.articulo, \'Sin inventario\')';
        $liquidadoExpr = $hasLiquidado ? 'v.liquidado' : 'NULL';
        $tipoPedidoExpr = $hasTipoPedido ? 'v.tipo_pedido' : 'NULL';
        $nombreClienteExpr = $hasNombreCliente ? 'v.nombre_cliente' : 'NULL';
        $direccionExpr = $hasDireccion ? 'v.direccion' : 'NULL';

        $stmt = Database::getInstance()->prepare(
            "SELECT hc.id,
                    hc.tipo,
                    hc.valor,
                    hc.concepto,
                    hc.usuario,
                    hc.fecha,
                    'caja'   AS origen,
                    NULL     AS orden_id,
                    NULL     AS liquidado,
                    NULL     AS tipo_pedido,
                    NULL     AS nombre_cliente,
                    NULL     AS direccion
             FROM historial_caja hc
             WHERE DATE(hc.fecha) = ?
                             AND NOT (hc.tipo = 'ingreso' AND hc.concepto LIKE 'Liquidaci%')

             UNION ALL

             SELECT v.id,
                    'venta'      AS tipo,
                    v.total      AS valor,
                      {$conceptoExpr} AS concepto,
                    v.usuario,
                    v.fecha,
                    'ventas'     AS origen,
                    v.orden_id,
                      {$liquidadoExpr} AS liquidado,
                      {$tipoPedidoExpr} AS tipo_pedido,
                      {$nombreClienteExpr} AS nombre_cliente,
                      {$direccionExpr} AS direccion
             FROM ventas v
             LEFT JOIN inventario i ON i.id = v.inventario_id
             WHERE DATE(v.fecha) = ?

             ORDER BY fecha DESC"
        );
        $stmt->execute([$hoy, $hoy]);
        return $stmt->fetchAll();
    }

    private function buildWhere(?string $desde, ?string $hasta): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($desde) && $this->validDate($desde)) {
            $conditions[] = 'fecha >= ?';
            $params[]     = $desde . ' 00:00:00';
        }
        if (!empty($hasta) && $this->validDate($hasta)) {
            $conditions[] = 'fecha <= ?';
            $params[]     = $hasta . ' 23:59:59';
        }

        return [$conditions ? ' WHERE ' . implode(' AND ', $conditions) : '', $params];
    }

    private function validDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }
}

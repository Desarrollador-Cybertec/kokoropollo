<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Venta
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

    /**
     * Registra una línea de venta.
     * Si inventarioId es null → porción/servicio virtual (no descuenta stock).
     */
    public function store(
        string  $ordenId,
        ?int    $inventarioId,
        int     $cantidad,
        float   $precioUnitario,
        float   $total,
        string  $usuario,
        string  $tipoPedido      = 'local',
        ?string $nombreCliente   = null,
        ?string $telefono        = null,
        ?string $direccion       = null,
        ?string $itemDescripcion = null,
    ): int {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            if ($inventarioId !== null) {
                // Ítem de inventario: verificar y descontar stock
                $check = $pdo->prepare('SELECT cantidad FROM inventario WHERE id = ? FOR UPDATE');
                $check->execute([$inventarioId]);
                $stock = (int) $check->fetchColumn();

                if ($stock < $cantidad) {
                    $pdo->rollBack();
                    throw new \RuntimeException("Stock insuficiente. Disponible: {$stock}.");
                }

                $pdo->prepare('UPDATE inventario SET cantidad = cantidad - ? WHERE id = ?')
                    ->execute([$cantidad, $inventarioId]);
            }

            $fields = ['orden_id', 'inventario_id', 'cantidad', 'precio_unitario', 'total', 'usuario', 'fecha'];
            $values = [$ordenId, $inventarioId, $cantidad, $precioUnitario, $total, $usuario];
            $placeholders = ['?', '?', '?', '?', '?', '?', 'NOW()'];

            if (self::hasColumn('ventas', 'item_descripcion')) {
                $fields[] = 'item_descripcion';
                $values[] = $itemDescripcion;
                $placeholders[] = '?';
            }
            if (self::hasColumn('ventas', 'tipo_pedido')) {
                $fields[] = 'tipo_pedido';
                $values[] = $tipoPedido;
                $placeholders[] = '?';
            }
            if (self::hasColumn('ventas', 'nombre_cliente')) {
                $fields[] = 'nombre_cliente';
                $values[] = $nombreCliente;
                $placeholders[] = '?';
            }
            if (self::hasColumn('ventas', 'telefono')) {
                $fields[] = 'telefono';
                $values[] = $telefono;
                $placeholders[] = '?';
            }
            if (self::hasColumn('ventas', 'direccion')) {
                $fields[] = 'direccion';
                $values[] = $direccion;
                $placeholders[] = '?';
            }

            $sql = 'INSERT INTO ventas (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $id = (int) $pdo->lastInsertId();

            $pdo->commit();
            return $id;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function sumToday(): float
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT COALESCE(SUM(total), 0) FROM ventas WHERE DATE(fecha) = CURDATE()'
        );
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    public function sumPendingLiquidation(): float
    {
        if (!self::hasColumn('ventas', 'liquidado')) {
            return 0.0;
        }

        $stmt = Database::getInstance()->prepare(
            'SELECT COALESCE(SUM(total), 0) FROM ventas WHERE liquidado = 0'
        );
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    public function countPendingLiquidation(): int
    {
        if (!self::hasColumn('ventas', 'liquidado')) {
            return 0;
        }

        $stmt = Database::getInstance()->prepare(
            'SELECT COUNT(*) FROM ventas WHERE liquidado = 0'
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function markAllLiquidated(): void
    {
        if (!self::hasColumn('ventas', 'liquidado')) {
            return;
        }

        // A-04: filtrar por fecha actual para no liquidar ventas de días anteriores
        Database::getInstance()->prepare(
            'UPDATE ventas SET liquidado = 1 WHERE liquidado = 0 AND DATE(fecha) = CURDATE()'
        )->execute();
    }

    /**
     * C-04: Liquidación atómica — bloquea caja y ventas con FOR UPDATE,
     * acredita el monto y marca liquidado en una sola transacción.
     *
     * @return array{pendiente:float,count:int,nuevo_total:float}
     * @throws \RuntimeException si no hay ventas pendientes.
     */
    public function liquidarACaja(): array
    {
        if (!self::hasColumn('ventas', 'liquidado')) {
            throw new \RuntimeException('La columna ventas.liquidado no existe. Ejecuta las migraciones pendientes antes de liquidar.');
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            // Bloquear fila de caja
            $pdo->query('SELECT total FROM caja WHERE id = 1 FOR UPDATE');

            // Calcular y bloquear ventas pendientes de hoy
            $stmt = $pdo->query(
                'SELECT COALESCE(SUM(total),0) AS pendiente, COUNT(*) AS cuenta
                 FROM ventas WHERE liquidado = 0 AND DATE(fecha) = CURDATE() FOR UPDATE'
            );
            $row       = $stmt->fetch();
            $pendiente = (float) $row['pendiente'];
            $count     = (int)   $row['cuenta'];

            if ($pendiente <= 0.0) {
                $pdo->rollBack();
                throw new \RuntimeException('No hay ventas pendientes de liquidar.');
            }

            // Ingresar a caja
            $pdo->prepare('UPDATE caja SET total = total + ? WHERE id = 1')->execute([$pendiente]);

            // Marcar ventas como liquidadas (solo del día actual)
            $pdo->prepare(
                'UPDATE ventas SET liquidado = 1 WHERE liquidado = 0 AND DATE(fecha) = CURDATE()'
            )->execute();

            $nuevoTotal = (float) $pdo->query('SELECT total FROM caja WHERE id = 1')->fetchColumn();
            $pdo->commit();
            return ['pendiente' => $pendiente, 'count' => $count, 'nuevo_total' => $nuevoTotal];
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /** Solo cuenta cuartos de Pollo Crudo (ítems de inventario real) */
    public function sumCuartosPolloVendidos(): int
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT COALESCE(SUM(v.cantidad), 0)
             FROM ventas v
             JOIN inventario i ON i.id = v.inventario_id
             WHERE i.categoria = 'Pollo Crudo'"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function allToday(): array
    {
        $hasItemDescripcion = self::hasColumn('ventas', 'item_descripcion');
        $articuloExpr = $hasItemDescripcion
            ? 'COALESCE(i.articulo, v.item_descripcion)'
            : 'COALESCE(i.articulo, \'Sin inventario\')';
        $categoriaExpr = $hasItemDescripcion
            ? 'COALESCE(i.categoria, \'Porciones\')'
            : 'COALESCE(i.categoria, \'Sin categoría\')';

        $stmt = Database::getInstance()->prepare(
            "SELECT v.id, v.orden_id,
                    {$articuloExpr} AS articulo,
                    {$categoriaExpr} AS categoria,
                    v.cantidad, v.precio_unitario, v.total, v.fecha
             FROM ventas v
             LEFT JOIN inventario i ON i.id = v.inventario_id
             WHERE DATE(v.fecha) = CURDATE()
             ORDER BY v.orden_id DESC, v.id ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

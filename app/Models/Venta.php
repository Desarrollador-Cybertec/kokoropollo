<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Venta
{
    public function store(
        string  $ordenId,
        int     $inventarioId,
        int     $cantidad,
        float   $precioUnitario,
        float   $total,
        string  $usuario,
        string  $tipoPedido    = 'local',
        ?string $nombreCliente = null,
        ?string $telefono      = null,
        ?string $direccion     = null,
    ): int {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $check = $pdo->prepare('SELECT cantidad FROM inventario WHERE id = ? FOR UPDATE');
            $check->execute([$inventarioId]);
            $stock = (int) $check->fetchColumn();

            if ($stock < $cantidad) {
                $pdo->rollBack();
                throw new \RuntimeException("Stock insuficiente. Disponible: {$stock}.");
            }

            $pdo->prepare('UPDATE inventario SET cantidad = cantidad - ? WHERE id = ?')
                ->execute([$cantidad, $inventarioId]);

            $stmt = $pdo->prepare(
                'INSERT INTO ventas
                 (orden_id, inventario_id, cantidad, precio_unitario, total, usuario,
                  tipo_pedido, nombre_cliente, telefono, direccion, fecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $ordenId, $inventarioId, $cantidad, $precioUnitario, $total, $usuario,
                $tipoPedido, $nombreCliente, $telefono, $direccion,
            ]);
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
        $stmt = Database::getInstance()->prepare(
            'SELECT COALESCE(SUM(total), 0) FROM ventas WHERE liquidado = 0'
        );
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    public function countPendingLiquidation(): int
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT COUNT(*) FROM ventas WHERE liquidado = 0'
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function markAllLiquidated(): void
    {
        Database::getInstance()->prepare(
            'UPDATE ventas SET liquidado = 1 WHERE liquidado = 0'
        )->execute();
    }

    /** Suma total de cuartos de Pollo Crudo vendidos desde el inicio del sistema */
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
        $stmt = Database::getInstance()->prepare(
            'SELECT v.id, v.orden_id, i.articulo, i.categoria, v.cantidad,
                    v.precio_unitario, v.total, v.fecha
             FROM ventas v
             JOIN inventario i ON i.id = v.inventario_id
             WHERE DATE(v.fecha) = CURDATE()
             ORDER BY v.orden_id DESC, v.id ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

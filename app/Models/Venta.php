<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Venta
{
    public function store(
        int    $inventarioId,
        int    $cantidad,
        float  $precioUnitario,
        float  $total,
        string $usuario,
    ): int {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO ventas (inventario_id, cantidad, precio_unitario, total, usuario, fecha)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$inventarioId, $cantidad, $precioUnitario, $total, $usuario]);
        return (int) $pdo->lastInsertId();
    }

    public function sumToday(): float
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT COALESCE(SUM(total), 0) FROM ventas WHERE DATE(fecha) = CURDATE()'
        );
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    public function allToday(): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT v.id, i.articulo, v.cantidad, v.precio_unitario, v.total, v.fecha
             FROM ventas v
             JOIN inventario i ON i.id = v.inventario_id
             WHERE DATE(v.fecha) = CURDATE()
             ORDER BY v.fecha DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

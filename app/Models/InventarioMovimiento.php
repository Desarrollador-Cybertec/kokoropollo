<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class InventarioMovimiento
{
    public function registrar(int $inventarioId, string $tipo, int $cantidad, string $usuario): void
    {
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO inventario_movimientos (inventario_id, tipo, cantidad, usuario) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$inventarioId, $tipo, $cantidad, $usuario]);
    }

    /** Retorna todos los movimientos agrupados por inventario_id, más recientes primero */
    public function todosAgrupados(int $limitePorArticulo = 15): array
    {
        $stmt = Database::getInstance()->query(
            'SELECT inventario_id, tipo, cantidad, usuario, creado
             FROM inventario_movimientos
             ORDER BY creado DESC'
        );
        $rows    = $stmt->fetchAll();
        $grouped = [];
        $conteo  = [];
        foreach ($rows as $row) {
            $iid = (int) $row['inventario_id'];
            $conteo[$iid] = ($conteo[$iid] ?? 0) + 1;
            if ($conteo[$iid] <= $limitePorArticulo) {
                $grouped[$iid][] = $row;
            }
        }
        return $grouped;
    }
}

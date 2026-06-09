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

    /**
     * Retorna movimientos filtrados con JOIN a inventario, paginados.
     * @return array{total:int, por_pagina:int, movimientos:array}
     */
    public function filtrar(
        ?int    $inventarioId,
        ?string $categoria,
        ?string $desde,
        ?string $hasta,
        int     $pagina    = 1,
        int     $porPagina = 50
    ): array {
        $db     = Database::getInstance();
        $where  = [];
        $params = [];

        if ($inventarioId !== null) {
            $where[]  = 'm.inventario_id = ?';
            $params[] = $inventarioId;
        }

        if ($categoria !== null) {
            if ($categoria === 'Pollo Crudo') {
                $where[] = "i.categoria IN (?, ?, ?, ?)";
                array_push($params, 'Pollo Crudo', 'Pollo', 'Asado', 'Broaster');
            } elseif ($categoria === 'Acompañamientos') {
                $where[] = "i.categoria IN (?, ?, ?)";
                array_push($params, 'Acompañamientos', 'Papas', 'Salsas');
            } else {
                $where[]  = 'i.categoria = ?';
                $params[] = $categoria;
            }
        }

        if ($desde !== null) {
            $where[]  = 'DATE(m.creado) >= ?';
            $params[] = $desde;
        }
        if ($hasta !== null) {
            $where[]  = 'DATE(m.creado) <= ?';
            $params[] = $hasta;
        }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $base     = "FROM inventario_movimientos m JOIN inventario i ON m.inventario_id = i.id $whereStr";

        $countStmt = $db->prepare("SELECT COUNT(*) $base");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($pagina - 1) * $porPagina;
        $stmt   = $db->prepare(
            "SELECT m.inventario_id, i.articulo, i.categoria, m.tipo, m.cantidad, m.usuario, m.creado
             $base ORDER BY m.creado DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([...$params, $porPagina, $offset]);
        $movimientos = $stmt->fetchAll();

        foreach ($movimientos as &$m) {
            if (in_array($m['categoria'], ['Pollo', 'Asado', 'Broaster'], strict: true)) {
                $m['categoria'] = 'Pollo Crudo';
            } elseif (in_array($m['categoria'], ['Papas', 'Salsas'], strict: true)) {
                $m['categoria'] = 'Acompañamientos';
            }
        }
        unset($m);

        return ['total' => $total, 'por_pagina' => $porPagina, 'movimientos' => $movimientos];
    }
}

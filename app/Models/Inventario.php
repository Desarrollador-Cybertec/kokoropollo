<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Inventario
{
    public function all(): array
    {
        $stmt = Database::getInstance()->query(
            'SELECT id, articulo, categoria, cantidad, valor FROM inventario ORDER BY categoria, articulo ASC'
        );
        return $stmt->fetchAll();
    }

    public function search(string $term): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT id, articulo, categoria, cantidad, valor FROM inventario
             WHERE articulo LIKE ? ORDER BY categoria, articulo ASC'
        );
        $stmt->execute(['%' . $term . '%']);
        return $stmt->fetchAll();
    }

    public function find(int $id): array|false
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT id, articulo, categoria, cantidad, valor FROM inventario WHERE id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(string $articulo, string $categoria, int $cantidad, float $valor): void
    {
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO inventario (articulo, categoria, cantidad, valor) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$articulo, $categoria, $cantidad, $valor]);
    }

    public function update(int $id, string $articulo, string $categoria, int $cantidad, float $valor): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE inventario SET articulo = ?, categoria = ?, cantidad = ?, valor = ? WHERE id = ?'
        );
        $stmt->execute([$articulo, $categoria, $cantidad, $valor, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::getInstance()->prepare(
            'DELETE FROM inventario WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public function forSelect(): array
    {
        $stmt = Database::getInstance()->query(
            'SELECT id, articulo, categoria, cantidad, valor FROM inventario
             WHERE cantidad > 0 ORDER BY categoria, articulo ASC'
        );
        return $stmt->fetchAll();
    }

    public function forSelectGrouped(): array
    {
        $rows = $this->forSelect();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['categoria']][] = $row;
        }
        return $grouped;
    }

    /** Valores válidos del ENUM categoria */
    public static function categorias(): array
    {
        return ['Pollo Crudo', 'Papas', 'Acompañamientos', 'Salsas', 'Bebidas', 'Otros'];
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Inventario
{
    public function all(): array
    {
        $stmt = Database::getInstance()->query(
            'SELECT id, articulo, cantidad, valor FROM inventario ORDER BY articulo ASC'
        );
        return $stmt->fetchAll();
    }

    public function search(string $term): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT id, articulo, cantidad, valor FROM inventario
             WHERE articulo LIKE ? ORDER BY articulo ASC'
        );
        $stmt->execute(['%' . $term . '%']);
        return $stmt->fetchAll();
    }

    public function find(int $id): array|false
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT id, articulo, cantidad, valor FROM inventario WHERE id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(string $articulo, int $cantidad, float $valor): void
    {
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO inventario (articulo, cantidad, valor) VALUES (?, ?, ?)'
        );
        $stmt->execute([$articulo, $cantidad, $valor]);
    }

    public function update(int $id, string $articulo, int $cantidad, float $valor): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE inventario SET articulo = ?, cantidad = ?, valor = ? WHERE id = ?'
        );
        $stmt->execute([$articulo, $cantidad, $valor, $id]);
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
            'SELECT id, articulo, valor FROM inventario ORDER BY articulo ASC'
        );
        return $stmt->fetchAll();
    }
}

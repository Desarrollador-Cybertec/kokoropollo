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

    /** Suma o resta delta al stock; nunca baja de 0 */
    public function ajustar(int $id, int $delta): void
    {
        if ($delta >= 0) {
            $stmt = Database::getInstance()->prepare(
                'UPDATE inventario SET cantidad = cantidad + ? WHERE id = ?'
            );
        } else {
            $stmt = Database::getInstance()->prepare(
                'UPDATE inventario SET cantidad = GREATEST(0, CAST(cantidad AS SIGNED) + ?) WHERE id = ?'
            );
        }
        $stmt->execute([$delta, $id]);
    }

    /** Descuenta 1 unidad de forma atómica; retorna false si stock ya es 0 */
    public function deductOne(int $id): bool
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE inventario SET cantidad = cantidad - 1 WHERE id = ? AND cantidad > 0'
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function countCritico(): int
    {
        $stmt = Database::getInstance()->query(
            "SELECT COUNT(*) FROM inventario
             WHERE (categoria = 'Pollo Crudo' AND cantidad < 4)
                OR (categoria != 'Pollo Crudo' AND cantidad <= 5)"
        );
        return (int) $stmt->fetchColumn();
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

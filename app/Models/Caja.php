<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Caja
{
    public function getTotal(): float
    {
        $stmt = Database::getInstance()->query(
            'SELECT total FROM caja WHERE id = 1'
        );
        return (float) ($stmt->fetchColumn() ?? 0);
    }

    public function updateTotal(float $nuevo): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE caja SET total = ? WHERE id = 1'
        );
        $stmt->execute([$nuevo]);
    }

    /**
     * C-04: Ajuste atómico con FOR UPDATE para evitar race conditions.
     * @throws \RuntimeException si saldo insuficiente en retiro.
     */
    public function ajustar(string $tipo, float $valor): float
    {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            $total = (float) $pdo->query('SELECT total FROM caja WHERE id = 1 FOR UPDATE')->fetchColumn();

            if ($tipo === 'retiro' && $valor > $total) {
                $pdo->rollBack();
                throw new \RuntimeException('No puede retirar más de lo que hay en caja.');
            }

            $nuevo = $tipo === 'ingreso' ? $total + $valor : $total - $valor;
            $pdo->prepare('UPDATE caja SET total = ? WHERE id = 1')->execute([$nuevo]);
            $pdo->commit();
            return $nuevo;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}

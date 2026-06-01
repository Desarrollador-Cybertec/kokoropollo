<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class RetiroSeguridad
{
    public function crear(float $valor, string $motivo, int $usuarioId): int
    {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $totalCaja = (float) $pdo->query('SELECT total FROM caja WHERE id = 1')->fetchColumn();
            if ($valor > $totalCaja) {
                $pdo->rollBack();
                throw new \RuntimeException('Saldo insuficiente en caja para el ALSÉ.');
            }

            $pdo->prepare(
                'INSERT INTO retiros_seguridad (valor, motivo, usuario_id) VALUES (?, ?, ?)'
            )->execute([$valor, $motivo, $usuarioId]);

            $id = (int) $pdo->lastInsertId();

            $pdo->prepare('UPDATE caja SET total = total - ? WHERE id = 1')->execute([$valor]);

            $pdo->commit();
            return $id;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Suma de ALSÉS del día actual (para precálculo del cierre) */
    public function sumHoy(): float
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT COALESCE(SUM(valor),0) FROM retiros_seguridad WHERE DATE(fecha) = CURDATE()'
        );
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    public function hoy(): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT r.*, u.nombre AS nombre_usuario
             FROM retiros_seguridad r
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE DATE(r.fecha) = CURDATE()
             ORDER BY r.fecha DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CajaApertura
{
    /** Denominaciones válidas en pesos colombianos (mayor a menor) */
    public const DENOMINACIONES = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 200, 100];

    public function existeHoy(): bool
    {
        return $this->getHoy() !== null;
    }

    public function getHoy(): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT a.*, u.nombre AS nombre_usuario
             FROM caja_aperturas a
             JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.fecha = CURDATE()
             LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['denominaciones'] = $this->getDenominaciones((int) $row['id']);
        return $row;
    }

    public function getById(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT a.*, u.nombre AS nombre_usuario
             FROM caja_aperturas a
             JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['denominaciones'] = $this->getDenominaciones($id);
        return $row;
    }

    /**
     * @param array<int, int> $denominaciones  [valor_denominacion => cantidad]
     */
    public function crear(int $usuarioId, string $observaciones, array $denominaciones): int
    {
        $base = $this->calcularBase($denominaciones);
        $pdo  = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO caja_aperturas (fecha, usuario_id, base_inicial, observaciones)
                 VALUES (CURDATE(), ?, ?, ?)'
            );
            $stmt->execute([$usuarioId, $base, $observaciones ?: null]);
            $aperturaId = (int) $pdo->lastInsertId();

            foreach ($denominaciones as $valor => $cantidad) {
                if (!in_array($valor, self::DENOMINACIONES, strict: true)) continue;
                $cantidad  = max(0, (int) $cantidad);
                $subtotal  = $valor * $cantidad;
                $pdo->prepare(
                    'INSERT INTO caja_apertura_denominaciones
                     (apertura_id, denominacion, cantidad, subtotal)
                     VALUES (?, ?, ?, ?)'
                )->execute([$aperturaId, $valor, $cantidad, $subtotal]);
            }

            $pdo->commit();
            return $aperturaId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ── helpers ─────────────────────────────────────────────────

    /** @param array<int, int> $denominaciones */
    private function calcularBase(array $denominaciones): float
    {
        $total = 0.0;
        foreach ($denominaciones as $valor => $cantidad) {
            $total += $valor * max(0, (int) $cantidad);
        }
        return $total;
    }

    /** @return array<int, array{denominacion:int, cantidad:int, subtotal:float}> */
    private function getDenominaciones(int $aperturaId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT denominacion, cantidad, subtotal
             FROM caja_apertura_denominaciones
             WHERE apertura_id = ?
             ORDER BY denominacion DESC'
        );
        $stmt->execute([$aperturaId]);
        return $stmt->fetchAll();
    }
}

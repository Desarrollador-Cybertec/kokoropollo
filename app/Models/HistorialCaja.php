<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class HistorialCaja
{
    public function create(string $tipo, float $valor, string $concepto, string $usuario): void
    {
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO historial_caja (tipo, valor, concepto, usuario, fecha)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$tipo, $valor, $concepto, $usuario]);
    }

    public function filter(?string $desde, ?string $hasta): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($desde) && $this->validDate($desde)) {
            $conditions[] = 'fecha >= ?';
            $params[]     = $desde . ' 00:00:00';
        }
        if (!empty($hasta) && $this->validDate($hasta)) {
            $conditions[] = 'fecha <= ?';
            $params[]     = $hasta . ' 23:59:59';
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $sql   = 'SELECT * FROM historial_caja' . $where . ' ORDER BY fecha DESC';

        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function validDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }
}

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
}

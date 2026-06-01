<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Configuracion
{
    private function ensureTable(): void
    {
        Database::getInstance()->exec(
            "CREATE TABLE IF NOT EXISTS `configuracion` (
                `clave` VARCHAR(100) NOT NULL,
                `valor` VARCHAR(500) NOT NULL DEFAULT '',
                PRIMARY KEY (`clave`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function get(string $clave, string $default = '0'): string
    {
        $result = $this->getMany([$clave]);
        return $result[$clave] ?? $default;
    }

    public function getMany(array $claves): array
    {
        if (empty($claves)) return [];

        $this->ensureTable();

        $placeholders = implode(',', array_fill(0, count($claves), '?'));
        $stmt = Database::getInstance()->prepare(
            "SELECT clave, valor FROM configuracion WHERE clave IN ($placeholders)"
        );
        $stmt->execute($claves);
        $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        return array_merge(array_fill_keys($claves, '0'), $rows);
    }

    public function setMany(array $datos): void
    {
        $this->ensureTable();

        $stmt = Database::getInstance()->prepare(
            'INSERT INTO configuracion (clave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        foreach ($datos as $clave => $valor) {
            $stmt->execute([(string) $clave, (string) $valor]);
        }
    }
}

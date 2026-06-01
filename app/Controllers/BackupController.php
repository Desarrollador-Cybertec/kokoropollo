<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Database, Request};
use App\Enums\Rol;
use App\Middleware\RoleMiddleware;

final class BackupController
{
    public function download(Request $request): never
    {
        RoleMiddleware::require(Rol::Jefe);

        $fecha    = date('Y-m-d_H-i-s');
        $filename = "kokoropollo_backup_{$fecha}.sql";

        header('Content-Type: application/octet-stream; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache');
        header('Pragma: no-cache');

        $pdo = Database::getInstance();

        echo "-- ============================================================\n";
        echo "-- Kokoro Pollo — Backup completo\n";
        echo "-- Generado: " . date('Y-m-d H:i:s') . "\n";
        echo "-- ============================================================\n\n";
        echo "SET FOREIGN_KEY_CHECKS = 0;\n";
        echo "SET NAMES utf8mb4;\n\n";

        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            echo "-- ── {$table} ─────────────────────────────────────────────\n";

            // Estructura
            $ddlRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $ddl    = $ddlRow['Create Table'] ?? $ddlRow[array_key_last($ddlRow)];
            echo "DROP TABLE IF EXISTS `{$table}`;\n";
            echo $ddl . ";\n\n";

            // Datos
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($rows)) {
                continue;
            }

            $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
            foreach ($rows as $row) {
                $vals = array_map(
                    fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                    $row
                );
                echo "INSERT INTO `{$table}` ({$cols}) VALUES (" . implode(', ', $vals) . ");\n";
            }
            echo "\n";
        }

        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        echo "-- FIN DEL BACKUP\n";
        exit;
    }
}

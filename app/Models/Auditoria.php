<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Auditoria
{
    private const POR_PAGINA = 100;

    public function registrar(string $usuario, string $modulo, string $accion, string $descripcion): void
    {
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO auditoria (usuario, modulo, accion, descripcion) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$usuario, $modulo, $accion, substr($descripcion, 0, 255)]);
    }

    public function filtrar(int $pagina = 1, string $modulo = '', string $usuario = ''): array
    {
        $where  = [];
        $params = [];

        if ($modulo !== '') {
            $where[]  = 'modulo = ?';
            $params[] = $modulo;
        }
        if ($usuario !== '') {
            $where[]  = 'usuario LIKE ?';
            $params[] = '%' . $usuario . '%';
        }

        $sql    = 'SELECT * FROM auditoria';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql   .= ' ORDER BY fecha DESC LIMIT ? OFFSET ?';

        $offset   = ($pagina - 1) * self::POR_PAGINA;
        $params[] = self::POR_PAGINA;
        $params[] = $offset;

        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countFiltrar(string $modulo = '', string $usuario = ''): int
    {
        $where  = [];
        $params = [];

        if ($modulo !== '') {
            $where[]  = 'modulo = ?';
            $params[] = $modulo;
        }
        if ($usuario !== '') {
            $where[]  = 'usuario LIKE ?';
            $params[] = '%' . $usuario . '%';
        }

        $sql = 'SELECT COUNT(*) FROM auditoria';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);

        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function modulos(): array
    {
        $stmt = Database::getInstance()->query(
            'SELECT DISTINCT modulo FROM auditoria ORDER BY modulo ASC'
        );
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function porPagina(): int
    {
        return self::POR_PAGINA;
    }
}

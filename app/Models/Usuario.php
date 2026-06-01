<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Enums\Rol;

final class Usuario
{
    public function all(): array
    {
        $stmt = Database::getInstance()->query(
            'SELECT id, nombre, usuario, rol FROM usuarios ORDER BY id ASC'
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): array|false
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT id, nombre, usuario, rol FROM usuarios WHERE id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(string $nombre, string $usuario, string $clave, Rol $rol): bool
    {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, ?)'
        );
        return $stmt->execute([$nombre, $usuario, $hash, $rol->value]);
    }

    public function update(int $id, string $nombre, string $usuario, Rol $rol, ?string $clave): bool
    {
        if ($clave !== null && $clave !== '') {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = Database::getInstance()->prepare(
                'UPDATE usuarios SET nombre = ?, usuario = ?, rol = ?, clave = ? WHERE id = ?'
            );
            return $stmt->execute([$nombre, $usuario, $rol->value, $hash, $id]);
        }

        $stmt = Database::getInstance()->prepare(
            'UPDATE usuarios SET nombre = ?, usuario = ?, rol = ? WHERE id = ?'
        );
        return $stmt->execute([$nombre, $usuario, $rol->value, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = Database::getInstance()->prepare(
            'DELETE FROM usuarios WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }
}

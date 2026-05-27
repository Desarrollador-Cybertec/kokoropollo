<?php

declare(strict_types=1);

namespace App\Enums;

enum Rol: string
{
    case Administrador = 'Administrador';
    case Empleado      = 'Empleado';

    public function dashboard(): string
    {
        return match($this) {
            Rol::Administrador => '/dashboard',
            Rol::Empleado      => '/dashboard/empleado',
        };
    }

    public function label(): string
    {
        return match($this) {
            Rol::Administrador => 'Administrador',
            Rol::Empleado      => 'Empleado',
        };
    }
}

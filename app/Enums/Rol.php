<?php

declare(strict_types=1);

namespace App\Enums;

enum Rol: string
{
    case Jefe          = 'Jefe';
    case Administrador = 'Administrador';
    case Empleado      = 'Empleado';

    /** Jerarquía: Jefe >= Administrador >= Empleado */
    public function atLeast(Rol $required): bool
    {
        $nivel = ['Empleado' => 1, 'Administrador' => 2, 'Jefe' => 3];
        return ($nivel[$this->value] ?? 0) >= ($nivel[$required->value] ?? 0);
    }

    public function dashboard(): string
    {
        return match($this) {
            Rol::Jefe          => '/dashboard/jefe',
            Rol::Administrador => '/dashboard',
            Rol::Empleado      => '/dashboard/empleado',
        };
    }

    public function label(): string
    {
        return match($this) {
            Rol::Jefe          => 'Jefe',
            Rol::Administrador => 'Administrador',
            Rol::Empleado      => 'Empleado',
        };
    }
}

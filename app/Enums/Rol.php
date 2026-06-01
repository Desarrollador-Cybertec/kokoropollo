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
        $nivel = [Rol::Empleado => 1, Rol::Administrador => 2, Rol::Jefe => 3];
        return $nivel[$this] >= $nivel[$required];
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

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, Session, View};
use App\Enums\Rol;
use App\Middleware\RoleMiddleware;
use App\Models\Auditoria;

final class AuditoriaController
{
    public function index(Request $request): void
    {
        RoleMiddleware::require(Rol::Jefe);

        $pagina  = max(1, (int) $request->get('pagina', '1'));
        $modulo  = trim($request->get('modulo', ''));
        $usuario = trim($request->get('usuario', ''));

        $auditoria  = new Auditoria();
        $registros  = $auditoria->filtrar($pagina, $modulo, $usuario);
        $total      = $auditoria->countFiltrar($modulo, $usuario);
        $modulos    = $auditoria->modulos();
        $porPagina  = Auditoria::porPagina();
        $totalPags  = (int) ceil($total / $porPagina);

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        View::render('auditoria/index', compact(
            'registros', 'total', 'totalPags', 'pagina',
            'modulo', 'usuario', 'modulos', 'porPagina'
        ) + ['dashboardUrl' => $rol?->dashboard() ?? '/dashboard']);
    }
}

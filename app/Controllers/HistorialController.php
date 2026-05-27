<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, View};
use App\Middleware\AuthMiddleware;
use App\Models\HistorialCaja;

final class HistorialController
{
    public function index(Request $request): void
    {
        AuthMiddleware::handle();

        $desde     = $request->get('desde', '');
        $hasta     = $request->get('hasta', '');
        $pagina    = max(1, (int) $request->get('pagina', 1));
        $porPagina = 50;

        $model        = new HistorialCaja();
        $total        = $model->count($desde ?: null, $hasta ?: null);
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina       = min($pagina, $totalPaginas);
        $registros    = $model->filterPaginated($desde ?: null, $hasta ?: null, $pagina, $porPagina);

        $totalIngresos = 0.0;
        $totalRetiros  = 0.0;
        foreach ($registros as $r) {
            if ($r['tipo'] === 'ingreso') $totalIngresos += (float) $r['valor'];
            else                          $totalRetiros  += (float) $r['valor'];
        }

        View::render('historial/index', compact(
            'registros', 'desde', 'hasta',
            'totalIngresos', 'totalRetiros',
            'pagina', 'totalPaginas', 'total'
        ));
    }
}

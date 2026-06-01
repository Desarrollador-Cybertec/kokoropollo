<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Logger, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{Caja, Configuracion, HistorialCaja, Inventario, Venta};

final class VentasController
{
    private const CATEGORIAS_CONFIG = [
        'Pollo Crudo'     => ['emoji' => '🐔', 'label' => 'Pollo Crudo'],
        'Papas'           => ['emoji' => '🥔', 'label' => 'Papas'],
        'Acompañamientos' => ['emoji' => '🍌', 'label' => 'Acompañ.'],
        'Salsas'          => ['emoji' => '🫙', 'label' => 'Salsas'],
        'Bebidas'         => ['emoji' => '🥤', 'label' => 'Bebidas'],
        'Otros'           => ['emoji' => '📦', 'label' => 'Otros'],
    ];

    public function index(Request $request): void
    {
        AuthMiddleware::handle();

        $hoy = date('Y-m-d');

        $productos = array_values(array_filter(
            (new Inventario())->forSelect(),
            static fn(array $p): bool => !in_array((string) ($p['categoria'] ?? ''), ['Asado', 'Broaster'], true)
        ));
        $productosJson    = json_encode(array_values($productos), JSON_UNESCAPED_UNICODE);
        $venta            = new Venta();
        $totalDia         = $venta->sumToday();
        $pendienteLiquidacion = $venta->sumPendingLiquidation();
        $rol              = Rol::tryFrom(Session::get('rol') ?? '');
        $dashboardUrl     = $rol?->dashboard() ?? '/dashboard';
        $categoriasConfig = self::CATEGORIAS_CONFIG;

        $cfg = (new Configuracion())->getMany([
            'precio_asado_cuarto', 'precio_asado_medio', 'precio_asado_entero',
            'precio_broaster_cuarto', 'precio_broaster_medio', 'precio_broaster_entero',
        ]);
        $preciosPolloJson = json_encode([
            'Asado'    => [
                'cuarto' => (float) $cfg['precio_asado_cuarto'],
                'medio'  => (float) $cfg['precio_asado_medio'],
                'entero' => (float) $cfg['precio_asado_entero'],
            ],
            'Broaster' => [
                'cuarto' => (float) $cfg['precio_broaster_cuarto'],
                'medio'  => (float) $cfg['precio_broaster_medio'],
                'entero' => (float) $cfg['precio_broaster_entero'],
            ],
        ]);

        // Datos de caja para el panel derecho
        $cajaTotal       = (new Caja())->getTotal();
        $cajaMovimientos = (new HistorialCaja())->filterUnifiedToday($hoy);
        $cajaIngresos    = 0.0;
        $cajaRetiros     = 0.0;
        foreach ($cajaMovimientos as $m) {
            if ($m['origen'] !== 'caja') continue;
            if ($m['tipo'] === 'ingreso') $cajaIngresos += (float) $m['valor'];
            if ($m['tipo'] === 'retiro')  $cajaRetiros  += (float) $m['valor'];
        }

        View::render('ventas/index', compact(
            'productos', 'productosJson', 'totalDia', 'dashboardUrl',
            'categoriasConfig', 'preciosPolloJson', 'pendienteLiquidacion',
            'cajaTotal', 'cajaMovimientos', 'cajaIngresos', 'cajaRetiros'
        ));
    }

    public function store(Request $request): void
    {
        AuthMiddleware::handle();
        header('Content-Type: application/json; charset=utf-8');

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Response::json(['status' => 'error', 'mensaje' => 'Token de seguridad inválido.'], code: 403);
        }

        [
            'ordenId'        => $ordenId,
            'inventarioId'   => $inventarioId,
            'cantidad'       => $cantidad,
            'precioUnitario' => $precioUnitario,
        ] = $this->validateVentaInput($request->json() ?? []);

        $total   = $precioUnitario * $cantidad;
        $usuario = Session::get('usuario', '');

        try {
            $ventaId = (new Venta())->store(
                ordenId:        $ordenId,
                inventarioId:   $inventarioId,
                cantidad:       $cantidad,
                precioUnitario: $precioUnitario,
                total:          $total,
                usuario:        $usuario,
            );

            Logger::getInstance()->info('Venta registrada', [
                'id'      => $ventaId,
                'total'   => $total,
                'usuario' => $usuario,
            ]);

            Response::json(['status' => 'ok', 'id' => $ventaId, 'total' => $total]);
        } catch (\RuntimeException $e) {
            Response::json(['status' => 'error', 'mensaje' => $e->getMessage()], code: 422);
        }
    }

    public function liquidar(Request $request): void
    {
        AuthMiddleware::handle();
        header('Content-Type: application/json; charset=utf-8');

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Response::json(['status' => 'error', 'mensaje' => 'Token de seguridad inválido.'], code: 403);
        }

        $venta     = new Venta();
        $pendiente = $venta->sumPendingLiquidation();
        $count     = $venta->countPendingLiquidation();

        if ($pendiente <= 0) {
            Response::json(['status' => 'error', 'mensaje' => 'No hay ventas pendientes de liquidar.'], code: 422);
        }

        $usuario    = Session::get('usuario', '');
        $caja       = new Caja();
        $nuevoTotal = $caja->getTotal() + $pendiente;

        $caja->updateTotal($nuevoTotal);
        (new HistorialCaja())->create(
            'ingreso',
            $pendiente,
            "Liquidación: {$count} venta(s)",
            $usuario,
        );
        $venta->markAllLiquidated();

        Logger::getInstance()->info('Liquidación de ventas a caja', [
            'total'   => $pendiente,
            'count'   => $count,
            'usuario' => $usuario,
        ]);

        Response::json([
            'status'         => 'ok',
            'total'          => $pendiente,
            'count'          => $count,
            'nuevoCajaTotal' => $nuevoTotal,
        ]);
    }

    private function validateVentaInput(array $data): array
    {
        $ordenId        = substr(preg_replace('/[^a-zA-Z0-9]/', '', (string) ($data['orden_id'] ?? '')), 0, 12);
        $inventarioId   = (int) ($data['inventario_id']   ?? 0);
        $cantidad       = (int) ($data['cantidad']         ?? 0);
        $precioUnitario = (float) ($data['precio_unitario'] ?? 0);

        if ($inventarioId <= 0 || $cantidad <= 0 || $precioUnitario <= 0) {
            Response::json(['status' => 'error', 'mensaje' => 'Datos inválidos.'], code: 422);
        }

        return compact('ordenId', 'inventarioId', 'cantidad', 'precioUnitario');
    }
}

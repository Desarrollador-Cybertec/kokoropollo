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
        'Pollo Crudo'     => ['emoji' => '🐔', 'label' => 'Pollo'],
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

        $productos = (new Inventario())->forSelect();

        // Porciones virtuales (independientes del inventario)
        $porcCfg = (new Configuracion())->getMany([
            'porcion_papa_activa',     'porcion_papa_precio',
            'porcion_francesa_activa', 'porcion_francesa_precio',
            'porcion_maduro_activa',   'porcion_maduro_precio',
        ]);
        $porciones = [
            -1 => ['nombre' => 'Porción Papa Cocida', 'key' => 'papa',     'emoji' => '🥔'],
            -2 => ['nombre' => 'Porción Francesa',    'key' => 'francesa', 'emoji' => '🍟'],
            -3 => ['nombre' => 'Porción de Maduro',   'key' => 'maduro',   'emoji' => '🍌'],
        ];
        $virtuales = [];
        foreach ($porciones as $vid => $info) {
            $k      = $info['key'];
            $activa = ($porcCfg["porcion_{$k}_activa"] ?? '0') === '1';
            $precio = (float) ($porcCfg["porcion_{$k}_precio"] ?? 0);
            if ($activa) {
                $virtuales[] = [
                    'id'        => $vid,
                    'articulo'  => $info['nombre'],
                    'categoria' => 'Acompañamientos',
                    'cantidad'  => 9999,
                    'valor'     => $precio,
                    'es_virtual'=> true,
                ];
            }
        }
        // Virtuales van al inicio para que se vean primero en el filtro "Todos"
        $productos     = array_merge($virtuales, $productos);
        $productosJson = json_encode(array_values($productos), JSON_UNESCAPED_UNICODE);
        $venta            = new Venta();
        $totalDia         = $venta->sumToday();
        $pendienteLiquidacion = $venta->sumPendingLiquidation();
        $rol              = Rol::tryFrom(Session::get('rol') ?? '');
        $dashboardUrl     = $rol?->dashboard() ?? '/dashboard';
        $categoriasConfig = self::CATEGORIAS_CONFIG;

        $cfg = (new Configuracion())->getMany([
            'precio_asado_cuarto', 'precio_asado_medio', 'precio_asado_entero',
        ]);
        $preciosPolloJson = json_encode([
            'cuarto' => (float) $cfg['precio_asado_cuarto'],
            'medio'  => (float) $cfg['precio_asado_medio'],
            'entero' => (float) $cfg['precio_asado_entero'],
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

        $json = $request->json() ?? [];

        [
            'ordenId'        => $ordenId,
            'inventarioId'   => $inventarioId,
            'cantidad'       => $cantidad,
            'precioUnitario' => $precioUnitario,
            'itemDescripcion'=> $itemDescripcion,
        ] = $this->validateVentaInput($json);

        $tipoPedido    = in_array($json['tipo_pedido'] ?? 'local', ['local', 'llevar'], true)
                         ? $json['tipo_pedido'] : 'local';
        $nombreCliente = isset($json['nombre_cliente'])
                         ? substr(trim((string) $json['nombre_cliente']), 0, 100) ?: null
                         : null;
        $telefono      = isset($json['telefono'])
                         ? substr(trim((string) $json['telefono']), 0, 20) ?: null
                         : null;
        $direccion     = isset($json['direccion'])
                         ? substr(trim((string) $json['direccion']), 0, 255) ?: null
                         : null;

        $total   = $precioUnitario * $cantidad;
        $usuario = Session::get('usuario', '');

        try {
            $ventaId = (new Venta())->store(
                ordenId:         $ordenId,
                inventarioId:    $inventarioId,
                cantidad:        $cantidad,
                precioUnitario:  $precioUnitario,
                total:           $total,
                usuario:         $usuario,
                tipoPedido:      $tipoPedido,
                nombreCliente:   $nombreCliente,
                telefono:        $telefono,
                direccion:       $direccion,
                itemDescripcion: $itemDescripcion,
            );

            // Descontar empaque automático en el primer ítem de cada pedido para llevar
            $empaqueId = 0;
            $primerItem = filter_var($json['primer_item'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($primerItem && $tipoPedido === 'llevar') {
                $empCfg    = (new Configuracion())->getMany(['empaque_activo', 'empaque_inventario_id']);
                $empaqueId = (int) ($empCfg['empaque_inventario_id'] ?? 0);
                if (($empCfg['empaque_activo'] ?? '0') === '1' && $empaqueId > 0) {
                    (new Inventario())->deductOne($empaqueId);
                }
            }

            Logger::getInstance()->info('Venta registrada', [
                'id'      => $ventaId,
                'total'   => $total,
                'usuario' => $usuario,
            ]);

            Response::json(['status' => 'ok', 'id' => $ventaId, 'total' => $total, 'empaque_id' => $empaqueId]);
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
        $rawId          = $data['inventario_id'] ?? null;
        $cantidad       = (int) ($data['cantidad']         ?? 0);
        $precioUnitario = (float) ($data['precio_unitario'] ?? 0);

        // inventario_id null = porción virtual; negativo = también virtual (del cliente JS)
        $inventarioId   = ($rawId === null || (int)$rawId <= 0) ? null : (int) $rawId;
        $itemDescripcion = $inventarioId === null
            ? substr(trim((string) ($data['item_descripcion'] ?? '')), 0, 150)
            : null;

        if ($cantidad <= 0 || $precioUnitario <= 0) {
            Response::json(['status' => 'error', 'mensaje' => 'Datos inválidos.'], code: 422);
        }

        return compact('ordenId', 'inventarioId', 'cantidad', 'precioUnitario', 'itemDescripcion');
    }
}

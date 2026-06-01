<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Database, Logger, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{Auditoria, Caja, Configuracion, HistorialCaja, Inventario, Venta};

final class VentasController
{
    private const CATEGORIAS_META = [
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

        // Mostrar solo categorías que realmente tienen productos hoy
        $categoriasPresentes = [];
        foreach ($productos as $p) {
            $cat = (string) ($p['categoria'] ?? '');
            if ($cat !== '') $categoriasPresentes[$cat] = true;
        }
        $categoriasConfig = [];
        foreach (self::CATEGORIAS_META as $cat => $meta) {
            if (isset($categoriasPresentes[$cat])) {
                $categoriasConfig[$cat] = $meta;
            }
        }
        // Si llega una categoría no mapeada, mostrarla igual con fallback.
        foreach (array_keys($categoriasPresentes) as $cat) {
            if (!isset($categoriasConfig[$cat])) {
                $categoriasConfig[$cat] = ['emoji' => '📦', 'label' => $cat];
            }
        }

        $venta            = new Venta();
        $totalDia         = $venta->sumToday();
        $pendienteLiquidacion = $venta->sumPendingLiquidation();
        $rol              = Rol::tryFrom(Session::get('rol') ?? '');
        $esAdmin          = $rol?->atLeast(Rol::Administrador) ?? false;
        $dashboardUrl     = $rol?->dashboard() ?? '/dashboard';

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
            'cajaTotal', 'cajaMovimientos', 'cajaIngresos', 'cajaRetiros', 'esAdmin'
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
            'ordenId'         => $ordenId,
            'inventarioId'    => $inventarioId,
            'rawInventarioId' => $rawInventarioId,
            'cantidad'        => $cantidad,
            'precioUnitario'  => $precioUnitario,
            'itemDescripcion' => $itemDescripcion,
        ] = $this->validateVentaInput($json);

        // C-01: Verificar precio contra BD — el cliente no puede enviar precios arbitrarios
        $precioEsperado = $this->getPrecioEsperado($inventarioId, $rawInventarioId);
        if ($precioEsperado > 0 && $precioUnitario < $precioEsperado) {
            Logger::getInstance()->warning('Intento de venta con precio manipulado', [
                'inventario_id'   => $inventarioId ?? $rawInventarioId,
                'precio_enviado'  => $precioUnitario,
                'precio_esperado' => $precioEsperado,
                'usuario'         => Session::get('usuario', ''),
            ]);
            Response::json(['status' => 'error', 'mensaje' => 'Precio inválido.'], code: 422);
        }

        $tipoPedidoRaw = in_array($json['tipo_pedido'] ?? 'local', ['local', 'llevar'], true)
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

        // Solo es "para llevar" si realmente trae datos de entrega.
        $tieneDatosEntrega = ($direccion !== null) || ($nombreCliente !== null) || ($telefono !== null);
        $tipoPedido        = ($tipoPedidoRaw === 'llevar' && $tieneDatosEntrega) ? 'llevar' : 'local';
        if ($tipoPedido === 'local') {
            $nombreCliente = null;
            $telefono      = null;
            $direccion     = null;
        }

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

            Response::json([
                'status'      => 'ok',
                'id'          => $ventaId,
                'total'       => $total,
                'empaque_id'  => $empaqueId,
                'tipo_pedido' => $tipoPedido,
            ]);
        } catch (\RuntimeException $e) {
            Response::json(['status' => 'error', 'mensaje' => $e->getMessage()], code: 422);
        }
    }

    public function liquidar(Request $request): void
    {
        AuthMiddleware::handle();
        // C-02: Solo Administrador o superior puede liquidar ventas a caja
        $rolActual = Rol::tryFrom(Session::get('rol') ?? '');
        if (!$rolActual?->atLeast(Rol::Administrador)) {
            Response::json(['status' => 'error', 'mensaje' => 'Acceso denegado.'], 403);
        }

        header('Content-Type: application/json; charset=utf-8');

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Response::json(['status' => 'error', 'mensaje' => 'Token de seguridad inválido.'], code: 403);
        }

        $usuario = Session::get('usuario', '');

        try {
            // C-04: liquidarACaja() es atómico con FOR UPDATE
            $resultado = (new Venta())->liquidarACaja();

            (new Auditoria())->registrar(
                $usuario, 'ventas', 'liquidar',
                'Liquidación a caja $' . number_format($resultado['pendiente'], 0, ',', '.') .
                ' (' . $resultado['count'] . ' ventas)'
            );

            Logger::getInstance()->info('Liquidación de ventas a caja', [
                'total'   => $resultado['pendiente'],
                'count'   => $resultado['count'],
                'usuario' => $usuario,
            ]);

            Response::json([
                'status'         => 'ok',
                'total'          => $resultado['pendiente'],
                'count'          => $resultado['count'],
                'nuevoCajaTotal' => $resultado['nuevo_total'],
            ]);
        } catch (\RuntimeException $e) {
            Response::json(['status' => 'error', 'mensaje' => $e->getMessage()], code: 422);
        }
    }

    private function validateVentaInput(array $data): array
    {
        $ordenId        = substr(preg_replace('/[^a-zA-Z0-9]/', '', (string) ($data['orden_id'] ?? '')), 0, 12);
        $rawId          = $data['inventario_id'] ?? null;
        $cantidad       = (int) ($data['cantidad']         ?? 0);
        $precioUnitario = (float) ($data['precio_unitario'] ?? 0);

        // inventario_id null = porción virtual; negativo = también virtual (del cliente JS)
        $rawInventarioId = ($rawId === null) ? 0 : (int) $rawId;
        $inventarioId    = $rawInventarioId > 0 ? $rawInventarioId : null;
        $itemDescripcion = $inventarioId === null
            ? substr(trim((string) ($data['item_descripcion'] ?? '')), 0, 150)
            : null;

        if ($cantidad <= 0 || $precioUnitario <= 0) {
            Response::json(['status' => 'error', 'mensaje' => 'Datos inválidos.'], code: 422);
        }

        return compact('ordenId', 'inventarioId', 'rawInventarioId', 'cantidad', 'precioUnitario', 'itemDescripcion');
    }

    /**
     * C-01: Obtiene el precio esperado desde BD para validar que el cliente no lo manipuló.
     * - Ítems reales: lee inventario.valor
     * - Porciones virtuales (-1 papa, -2 francesa, -3 maduro): lee configuracion
     * Retorna 0.0 si no se puede determinar (no bloquear en caso de fallo de config).
     */
    private function getPrecioEsperado(?int $inventarioId, int $rawInventarioId): float
    {
        if ($inventarioId !== null) {
            $stmt = Database::getInstance()->prepare(
                'SELECT valor FROM inventario WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$inventarioId]);
            return (float) ($stmt->fetchColumn() ?: 0);
        }

        $claveMap = [-1 => 'porcion_papa_precio', -2 => 'porcion_francesa_precio', -3 => 'porcion_maduro_precio'];
        if (isset($claveMap[$rawInventarioId])) {
            $stmt = Database::getInstance()->prepare(
                'SELECT valor FROM configuracion WHERE clave = ? LIMIT 1'
            );
            $stmt->execute([$claveMap[$rawInventarioId]]);
            return (float) ($stmt->fetchColumn() ?: 0);
        }

        return 0.0;
    }
}

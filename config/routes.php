<?php

declare(strict_types=1);

use App\Controllers\{
    AuthController,
    ConfigController,
    DashboardController,
    InventarioController,
    CajaController,
    HistorialController,
    VentasController,
    UsuariosController,
};

// ── Autenticación ──────────────────────────────────────────
$router->get('/',          [AuthController::class,      'showLogin']);
$router->post('/login',    [AuthController::class,      'login']);
$router->get('/logout',    [AuthController::class,      'logout']);

// ── Dashboards (ambas rutas → mismo controlador unificado) ─
$router->get('/dashboard',          [DashboardController::class, 'index']);
$router->get('/dashboard/empleado', [DashboardController::class, 'index']);

// ── Inventario ─────────────────────────────────────────────
$router->get('/inventario',         [InventarioController::class, 'index']);
$router->post('/inventario/store',  [InventarioController::class, 'store']);
$router->post('/inventario/update', [InventarioController::class, 'update']);
$router->post('/inventario/delete', [InventarioController::class, 'destroy']);

// ── Caja ───────────────────────────────────────────────────
$router->get('/caja',          [CajaController::class, 'index']);
$router->post('/caja',         [CajaController::class, 'process']);
$router->get('/caja/resumen',  [CajaController::class, 'resumen']);
$router->post('/caja/ajuste',  [CajaController::class, 'ajuste']);

// ── Historial ──────────────────────────────────────────────
$router->get('/historial', [HistorialController::class, 'index']);

// ── Ventas ─────────────────────────────────────────────────
$router->get('/ventas',            [VentasController::class, 'index']);
$router->post('/ventas/store',     [VentasController::class, 'store']);
$router->post('/ventas/liquidar',  [VentasController::class, 'liquidar']);

// ── Configuración (solo Administrador) ────────────────────
$router->get('/config',  [ConfigController::class, 'index']);
$router->post('/config', [ConfigController::class, 'save']);

// ── Usuarios (solo Administrador) ──────────────────────────
$router->get('/usuarios',           [UsuariosController::class, 'index']);
$router->get('/usuarios/list',      [UsuariosController::class, 'list']);
$router->post('/usuarios/create',   [UsuariosController::class, 'create']);
$router->post('/usuarios/update',   [UsuariosController::class, 'update']);
$router->post('/usuarios/delete',   [UsuariosController::class, 'destroy']);

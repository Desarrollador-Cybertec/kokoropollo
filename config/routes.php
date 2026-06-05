<?php

declare(strict_types=1);

use App\Controllers\{
    AlsesController,
    AuditoriaController,
    AuthController,
    BackupController,
    CajaAperturaController,
    CajaCierreController,
    CreditosController,
    ConfigController,
    ReportesController,
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

// ── Dashboards ─────────────────────────────────────────────
$router->get('/dashboard',          [DashboardController::class, 'index']);
$router->get('/dashboard/empleado', [DashboardController::class, 'index']);
$router->get('/dashboard/jefe',     [DashboardController::class, 'indexJefe']);

// ── Inventario ─────────────────────────────────────────────
$router->get('/inventario',         [InventarioController::class, 'index']);
$router->post('/inventario/store',      [InventarioController::class, 'store']);
$router->post('/inventario/update',     [InventarioController::class, 'update']);
$router->post('/inventario/movimiento', [InventarioController::class, 'movimiento']);
$router->post('/inventario/delete',     [InventarioController::class, 'destroy']);

// ── Caja ───────────────────────────────────────────────────
$router->get('/caja',               [CajaController::class, 'index']);
$router->post('/caja',              [CajaController::class, 'process']);
$router->get('/caja/resumen',       [CajaController::class, 'resumen']);
$router->post('/caja/ajuste',       [CajaController::class, 'ajuste']);

// ── Apertura de Caja (solo Admin) ──────────────────────────
$router->get('/caja/apertura',      [CajaAperturaController::class, 'index']);
$router->post('/caja/apertura',     [CajaAperturaController::class, 'store']);

// ── Cierre de Caja (solo Admin) ────────────────────────────
$router->get('/caja/cierre',        [CajaCierreController::class, 'index']);
$router->post('/caja/cierre',       [CajaCierreController::class, 'store']);

// ── Créditos a Empleados (solo Admin) ──────────────────────
$router->get('/creditos',           [CreditosController::class, 'index']);
$router->get('/creditos/list',      [CreditosController::class, 'list']);
$router->post('/creditos/crear',    [CreditosController::class, 'crear']);
$router->post('/creditos/pagar',    [CreditosController::class, 'pagar']);
$router->post('/creditos/vencer',   [CreditosController::class, 'vencer']);

// ── ALSÉS / Retiros de Seguridad (solo Admin) ──────────────
$router->post('/caja/alse',         [AlsesController::class, 'store']);

// ── Historial ──────────────────────────────────────────────
$router->get('/historial', [HistorialController::class, 'index']);

// ── Ventas ─────────────────────────────────────────────────
$router->get('/ventas',            [VentasController::class, 'index']);
$router->post('/ventas/store',     [VentasController::class, 'store']);
$router->post('/ventas/liquidar',  [VentasController::class, 'liquidar']);

// ── Configuración (solo Administrador) ────────────────────
$router->get('/config',                    [ConfigController::class, 'index']);
$router->post('/config',                   [ConfigController::class, 'save']);
$router->post('/config/reset-condimentos', [ConfigController::class, 'resetCondimentos']);

// ── Reportes Gerenciales (solo Administrador) ──────────────
$router->get('/reportes',            [ReportesController::class, 'index']);
$router->get('/reportes/diario',     [ReportesController::class, 'diario']);
$router->get('/reportes/semanal',    [ReportesController::class, 'semanal']);
$router->get('/reportes/mensual',    [ReportesController::class, 'mensual']);
$router->get('/reportes/productos',  [ReportesController::class, 'productos']);
$router->get('/reportes/empleados',  [ReportesController::class, 'empleados']);

// ── Backup (solo Jefe) ─────────────────────────────────────
$router->get('/backup', [BackupController::class, 'download']);

// ── Historial de precios ───────────────────────────────────
$router->get('/config/historial-precios', [ConfigController::class, 'historialPrecios']);

// ── Auditoría (solo Jefe) ──────────────────────────────────
$router->get('/auditoria', [AuditoriaController::class, 'index']);

// ── Usuarios (solo Administrador) ──────────────────────────
$router->get('/usuarios',           [UsuariosController::class, 'index']);
$router->get('/usuarios/list',      [UsuariosController::class, 'list']);
$router->post('/usuarios/create',   [UsuariosController::class, 'create']);
$router->post('/usuarios/update',   [UsuariosController::class, 'update']);
$router->post('/usuarios/delete',   [UsuariosController::class, 'destroy']);

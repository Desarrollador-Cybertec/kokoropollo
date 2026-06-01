# Kokoro Pollo — Documentación Completa del Sistema

> Sistema POS especializado para asadero de pollo tradicional colombiano.  
> Stack: **PHP 8.4** · **MySQL / MariaDB** (InnoDB) · **Tailwind CSS CDN** · **Vanilla JS**  
> Servidor local: `http://kokoropollo.test` vía **Laravel Herd**  
> Última actualización: 2026-06-01 (v3.0 — Sprints 1-3 de la segunda etapa)

---

## Índice

1. [Arquitectura general](#1-arquitectura-general)
2. [Base de datos — 13 tablas](#2-base-de-datos--13-tablas)
3. [Control de acceso por rol](#3-control-de-acceso-por-rol)
4. [Mapa de rutas](#4-mapa-de-rutas)
5. [Módulo: Autenticación](#5-módulo-autenticación)
6. [Módulo: Dashboards (3 perfiles)](#6-módulo-dashboards-3-perfiles)
7. [Módulo: Ventas (POS)](#7-módulo-ventas-pos)
8. [Módulo: Caja](#8-módulo-caja)
9. [Módulo: Apertura de Caja](#9-módulo-apertura-de-caja)
10. [Módulo: Cierre de Caja](#10-módulo-cierre-de-caja)
11. [Módulo: ALSÉS — Retiros de Seguridad](#11-módulo-alsés--retiros-de-seguridad)
12. [Módulo: Créditos a Empleados](#12-módulo-créditos-a-empleados)
13. [Módulo: Historial de Movimientos](#13-módulo-historial-de-movimientos)
14. [Módulo: Inventario](#14-módulo-inventario)
15. [Módulo: Usuarios](#15-módulo-usuarios)
16. [Módulo: Configuración](#16-módulo-configuración)
17. [Módulo: Reportes Gerenciales](#17-módulo-reportes-gerenciales)
18. [Módulo: Auditoría Operativa](#18-módulo-auditoría-operativa)
19. [Seguridad transversal](#19-seguridad-transversal)
20. [Checklist de verificación](#20-checklist-de-verificación)

---

## 1. Arquitectura general

```
Navegador
   │
   ▼
public/index.php  ←── punto de entrada único (.htaccess redirige todo aquí)
   │
   ▼
Router (GET / POST por ruta definida en config/routes.php)
   │
   ▼
Controller  →  Model (PDO / MySQL)  →  View (PHP template)
```

### Estructura de archivos

```
KokoroPollo/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php          ← index() + indexJefe()
│   │   ├── VentasController.php
│   │   ├── CajaController.php
│   │   ├── CajaAperturaController.php
│   │   ├── CajaCierreController.php
│   │   ├── AlsesController.php
│   │   ├── CreditosController.php
│   │   ├── HistorialController.php
│   │   ├── InventarioController.php
│   │   ├── UsuariosController.php
│   │   ├── ConfigController.php
│   │   ├── ReportesController.php           ← +empleados() +exportCsv()
│   │   └── AuditoriaController.php          ← NUEVO Sprint 3
│   ├── Models/
│   │   ├── Caja.php
│   │   ├── CajaApertura.php
│   │   ├── CajaCierre.php
│   │   ├── CreditoEmpleado.php
│   │   ├── RetiroSeguridad.php
│   │   ├── HistorialCaja.php
│   │   ├── Inventario.php                   ← +deductOne() +countCritico()
│   │   ├── Venta.php
│   │   ├── Configuracion.php
│   │   ├── Reporte.php                      ← +ventasPorEmpleado() +ventasEmpleadoPorDia()
│   │   ├── Usuario.php                      ← +find()
│   │   └── Auditoria.php                    ← NUEVO Sprint 3
│   ├── Core/  (Csrf, Database, Logger, Request, Response, Router, Session, View)
│   ├── Enums/
│   │   └── Rol.php                          ← 3 casos: Jefe, Administrador, Empleado + atLeast()
│   └── Middleware/ (AuthMiddleware, RoleMiddleware)  ← usa atLeast()
├── config/routes.php
├── database/
│   ├── schema.sql
│   └── migrations/
│       ├── 001_caja_aperturas.sql
│       ├── 002_caja_cierres.sql
│       ├── 003_creditos_empleados.sql
│       ├── 004_retiros_seguridad.sql
│       ├── 005_ventas_tipo_pedido.sql
│       ├── 006_rol_jefe.sql                 ← NUEVO: ENUM Jefe, migración admins → Jefe, borra Broaster
│       └── 008_auditoria.sql                ← NUEVO: tabla auditoria
├── public/
│   ├── index.php
│   ├── css/styles.css
│   ├── js/ventas.js                         ← sin Broaster, +primer_item, +empaque_id
│   └── img/logo.png
└── resources/views/
    ├── auth/login.php
    ├── dashboard/
    │   ├── index.php                        ← Admin 6 botones / Empleado 3 botones
    │   └── jefe.php                         ← NUEVO: dashboard ejecutivo
    ├── ventas/index.php                     ← sin sección Preparación (Broaster eliminado)
    ├── caja/index.php
    ├── caja/apertura.php
    ├── caja/cierre.php
    ├── creditos/index.php
    ├── historial/index.php
    ├── inventario/index.php
    ├── usuarios/index.php                   ← +opción Jefe en dropdown
    ├── config/index.php                     ← sin Broaster, +sección empaque automático
    ├── reportes/
    │   ├── index.php                        ← +tarjeta Por Empleado
    │   ├── diario.php                       ← +botón CSV
    │   ├── semanal.php                      ← +botón CSV
    │   ├── mensual.php                      ← +botón CSV
    │   ├── productos.php                    ← +botón CSV
    │   └── empleados.php                    ← NUEVO Sprint 2
    ├── auditoria/
    │   └── index.php                        ← NUEVO Sprint 3
    └── partials/ (head.php, toasts.php, confirm-modal.php)
```

### Identidad visual

- **Fondo:** `linear-gradient(135deg, #3b0a0a 0%, #4a0e0e 40%, #2b1a1a 100%)` en todas las páginas (inline style en `<body>`)
- **Acento principal:** Oro `#d4af37` / `#e6c857`
- **Cards:** `#3c1f1f`
- **CSS personalizado:** `public/css/styles.css` — botones, inputs, filas, toasts, scrollbar

---

## 2. Base de datos — 13 tablas

Base de datos: `kokoropollo` · Motor: InnoDB · Charset: utf8mb4

### Tablas originales (v1)

| Tabla | Descripción | Campos clave |
|---|---|---|
| `usuarios` | Cuentas del sistema | id, nombre, usuario (UNIQUE), clave (bcrypt), rol ENUM(**Jefe**/Administrador/Empleado) |
| `inventario` | Artículos en stock | id, articulo, categoria ENUM, cantidad (cuartos para Pollo Crudo), valor |
| `caja` | Saldo actual (fila única id=1) | id, total DECIMAL |
| `historial_caja` | Movimientos manuales de caja | id, tipo (ingreso/retiro), valor, concepto, usuario, fecha |
| `ventas` | Líneas de venta registradas | id, orden_id, inventario_id (FK), cantidad, precio_unitario, total, usuario, **tipo_pedido, nombre_cliente, telefono, direccion**, fecha, liquidado |
| `configuracion` | Parámetros clave-valor | clave (PK), valor |

### Tablas añadidas (v2)

| Tabla | Descripción |
|---|---|
| `caja_aperturas` | Una apertura por día con base inicial |
| `caja_apertura_denominaciones` | Detalle billetes/monedas en la apertura |
| `caja_cierres` | Cierre diario con arqueo y resultado |
| `caja_cierre_denominaciones` | Detalle del conteo físico en el cierre |
| `creditos_empleados` | Préstamos internos a empleados |
| `retiros_seguridad` | ALSÉS — traslados de seguridad |
| `auditoria` | Registro de cambios operativos críticos ← **NUEVO v3** |

### Tabla `auditoria` (nueva)

```sql
id          INT UNSIGNED AUTO_INCREMENT PK
usuario     VARCHAR(60)      -- quien hizo la acción
modulo      VARCHAR(40)      -- inventario | usuarios | config | creditos
accion      ENUM('crear','editar','eliminar','pagar')
descripcion VARCHAR(255)     -- texto legible: "Precio ¼ Asado: $8.000 → $9.000"
fecha       DATETIME DEFAULT CURRENT_TIMESTAMP
```

### Columnas en `ventas` (migración 005)

```sql
tipo_pedido    ENUM('local','llevar') NOT NULL DEFAULT 'local'
nombre_cliente VARCHAR(100) DEFAULT NULL
telefono       VARCHAR(20)  DEFAULT NULL
direccion      VARCHAR(255) DEFAULT NULL
```

### Claves en `configuracion`

```
precio_asado_cuarto           → Precio ¼ pollo asado
precio_asado_medio            → Precio ½ pollo asado
precio_asado_entero           → Precio pollo entero asado
condimentos_cuartos_offset    → Base del contador de condimentos
condimentos_pollos_por_ciclo  → Capacidad del ciclo (default 1000)
empaque_activo                → '0'/'1' — activa descuento automático
empaque_inventario_id         → ID del artículo de empaque en inventario
```

> **Eliminado en migración 006:** `precio_broaster_cuarto`, `precio_broaster_medio`, `precio_broaster_entero`

### Regla especial Pollo Crudo

- Unidad interna: **cuarto** (¼ de pollo); 1 pollo = 4 cuartos en BD
- Inventario almacena y descuenta cuartos; la UI convierte pollos ↔ cuartos
- Ventas: corte determina cuartos descontados (¼=1, ½=2, entero=4)

### Denominaciones soportadas (COP)
`100.000` · `50.000` · `20.000` · `10.000` · `5.000` · `2.000` · `1.000` · `500` · `200` · `100`

---

## 3. Control de acceso por rol

### Jerarquía de roles

```
Jefe (nivel 3)           →  acceso total
  └── Administrador (nivel 2)  →  operativa (caja, créditos, apertura/cierre)
        └── Empleado (nivel 1) →  ventas, caja básica, inventario
```

**Implementación:** `Rol::atLeast(Rol $required): bool` en `app/Enums/Rol.php`.  
`RoleMiddleware::require(Rol::X)` pasa si `$rolActual->atLeast(X)` — roles superiores siempre pasan.

```php
// Ejemplo: require(Rol::Administrador) → pasa Administrador Y Jefe
// require(Rol::Jefe) → pasa solo Jefe
```

### Tabla de permisos por módulo

| Módulo | Empleado | Administrador | Jefe |
|---|---|---|---|
| Ventas | ✅ | ✅ | ✅ |
| Caja | ✅ | ✅ | ✅ |
| Inventario | ✅ | ✅ | ✅ |
| Historial | ✅ | ✅ | ✅ |
| Apertura de Caja | ❌ | ✅ | ✅ |
| Cierre de Caja | ❌ | ✅ | ✅ |
| ALSÉS | ❌ | ✅ | ✅ |
| Créditos Empleados | ❌ | ✅ | ✅ |
| Usuarios | ❌ | ❌ | ✅ |
| Configuración | ❌ | ❌ | ✅ |
| Reportes | ❌ | ❌ | ✅ |
| Auditoría | ❌ | ❌ | ✅ |

**Sesión:** guarda `usuario_id`, `usuario`, `rol`  
**Migración 006:** todos los `Administrador` existentes se convierten a `Jefe` automáticamente al ejecutarse.

---

## 4. Mapa de rutas

| Método | Ruta | Controlador::método | Acceso |
|---|---|---|---|
| GET | `/` | AuthController::showLogin | Público |
| POST | `/login` | AuthController::login | Público |
| GET | `/logout` | AuthController::logout | Autenticado |
| GET | `/dashboard` | DashboardController::index | Autenticado |
| GET | `/dashboard/empleado` | DashboardController::index | Autenticado |
| GET | `/dashboard/jefe` | DashboardController::indexJefe | Jefe |
| GET | `/inventario` | InventarioController::index | Autenticado |
| POST | `/inventario/store` | InventarioController::store | Autenticado |
| POST | `/inventario/update` | InventarioController::update | Autenticado |
| POST | `/inventario/delete` | InventarioController::destroy | Autenticado |
| GET | `/caja` | CajaController::index | Autenticado |
| POST | `/caja` | CajaController::process | Autenticado |
| GET | `/caja/resumen` | CajaController::resumen | Autenticado (JSON) |
| POST | `/caja/ajuste` | CajaController::ajuste | Autenticado (JSON) |
| GET | `/caja/apertura` | CajaAperturaController::index | Admin+ |
| POST | `/caja/apertura` | CajaAperturaController::store | Admin+ |
| GET | `/caja/cierre` | CajaCierreController::index | Admin+ |
| POST | `/caja/cierre` | CajaCierreController::store | Admin+ |
| POST | `/caja/alse` | AlsesController::store | Admin+ (JSON) |
| GET | `/creditos` | CreditosController::index | Admin+ |
| POST | `/creditos/crear` | CreditosController::crear | Admin+ (JSON) |
| POST | `/creditos/pagar` | CreditosController::pagar | Admin+ (JSON) |
| POST | `/creditos/vencer` | CreditosController::vencer | Admin+ (JSON) |
| GET | `/historial` | HistorialController::index | Autenticado |
| GET | `/ventas` | VentasController::index | Autenticado |
| POST | `/ventas/store` | VentasController::store | Autenticado (JSON) |
| POST | `/ventas/liquidar` | VentasController::liquidar | Autenticado (JSON) |
| GET | `/config` | ConfigController::index | Jefe |
| POST | `/config` | ConfigController::save | Jefe |
| POST | `/config/reset-condimentos` | ConfigController::resetCondimentos | Jefe |
| GET | `/reportes` | ReportesController::index | Jefe |
| GET | `/reportes/diario` | ReportesController::diario | Jefe |
| GET | `/reportes/semanal` | ReportesController::semanal | Jefe |
| GET | `/reportes/mensual` | ReportesController::mensual | Jefe |
| GET | `/reportes/productos` | ReportesController::productos | Jefe |
| GET | `/reportes/empleados` | ReportesController::empleados | Jefe |
| GET | `/auditoria` | AuditoriaController::index | Jefe |
| GET | `/usuarios` | UsuariosController::index | Jefe |
| GET | `/usuarios/list` | UsuariosController::list | Jefe (JSON) |
| POST | `/usuarios/create` | UsuariosController::create | Jefe (JSON) |
| POST | `/usuarios/update` | UsuariosController::update | Jefe (JSON) |
| POST | `/usuarios/delete` | UsuariosController::destroy | Jefe (JSON) |

> **Admin+** = Administrador o Jefe (por jerarquía `atLeast`)

---

## 5. Módulo: Autenticación

**Vista:** `auth/login.php`

```
GET /  →  formulario con logo, campos usuario/contraseña, btn Ingresar

POST /login
  ├─► Valida CSRF (campo _token oculto)
  ├─► SELECT usuario WHERE usuario = ?
  ├─► password_verify(contraseña, hash_bcrypt)
  ├─► Si inválido → flash('error') + redirect('/')
  └─► Si válido:
        session_regenerate_id()
        Session::set('usuario_id', id)
        Session::set('usuario', usuario)
        Session::set('rol', rol)
        redirect según rol:
          Jefe          → /dashboard/jefe
          Administrador → /dashboard
          Empleado      → /dashboard/empleado
```

```
GET /logout → session_destroy() → redirect('/')
```

---

## 6. Módulo: Dashboards (3 perfiles)

Cada rol tiene su propio dashboard diferenciado.

### Dashboard del Empleado — `/dashboard/empleado`

3 botones: 📦 INVENTARIO · 💰 CAJA · 🛒 VENTAS

### Dashboard del Administrador — `/dashboard`

6 botones: 📦 INVENTARIO · 💰 CAJA · 🛒 VENTAS · 🔓 APERTURA · 🔒 CIERRE · 💳 CRÉDITOS

Widget de alerta de condimentos sobre los botones:

| % del ciclo | Nivel | Mensaje |
|---|---|---|
| < 50% | Sin alerta | — |
| 50-79% | Ámbar | ⚠️ Preparar condimentos — N/capacidad pollos |
| 80-99% | Rojo | 🔴 Condimentos críticos |
| ≥ 100% | Rojo intenso | 🚨 CONDIMENTOS AGOTADOS |

### Dashboard del Jefe — `/dashboard/jefe` ← NUEVO

**Layout:**
```
[Header: KOKORO POLLO | badge 👑 Jefe | logo]

[KPIs del día — 4 tarjetas]
  Ventas hoy | Utilidad estimada | En caja ahora | Pedidos hoy

[Panel de ALERTAS — aparece solo si hay algo]
  - Caja sin apertura      → link a /caja/apertura
  - Pendiente cerrar caja  → link a /caja/cierre (solo si hora >= 18:00)
  - Créditos vencidos      → link a /creditos
  - Artículos stock bajo   → link a /inventario
  - Alerta condimentos     → link a /config#condimentos
  - Pendiente de liquidar  → link a /ventas

[Resumen del mes — 4 KPIs]
  Ventas mes | Utilidad mes | Pedidos mes | Ticket promedio

[8 accesos rápidos]
  Reportes · Usuarios · Config · Auditoría · Caja · Créditos · Inventario · Ventas
```

**Datos calculados en DashboardController::indexJefe():**
- `Reporte::resumenDia($hoy)` → ventas, utilidad, pedidos
- `Caja::getTotal()` → efectivo en caja
- `Venta::sumPendingLiquidation()` → pendiente
- `CajaApertura::existeHoy()` + `CajaCierre::existeHoy()` → estado operativo
- `CreditoEmpleado::resumen()` → créditos vencidos, cartera
- `Inventario::countCritico()` → artículos bajo mínimo
- `Reporte::resumenPeriodo($mesDesde, $mesHasta)` → KPIs del mes
- Configuración → condimentos (ciclo, %, alerta)

---

## 7. Módulo: Ventas (POS)

**Vistas:** `ventas/index.php` · **JS:** `public/js/ventas.js`

### Eliminación de Broaster (Sprint 1)

El negocio únicamente vende **Pollo Asado**. Se eliminó completamente:
- El selector "¿Cómo lo quiere? Asado / Broaster" (sección `#seccionPreparacion`)
- La variable `preparacionActual` del JS
- La función `seleccionarPreparacion()` del JS
- Los precios Broaster de la BD y de Configuración
- El nodo anidado `PRECIOS_POLLO['Asado']['corte']` → ahora es `PRECIOS_POLLO['corte']`

**Flujo actual para Pollo Crudo:** seleccionar producto → seleccionar corte directamente → ajustar cantidad → agregar.  
**Nombre en carrito/factura:** `"Asado — ¼ Pierna-Pernil"`, `"Asado — Medio Pollo"`, etc.

### Layout pantalla dividida

- **Izquierda (2/5):** Panel de Caja — saldo, movimientos, añadir/retirar, ALSÉ
- **Derecha (3/5):** POS — tipo pedido, productos, configurador, carrito

### Flujo completo de una venta

**Paso 0 — Tipo de pedido**
```
[🏠 Local]  [🛵 Para llevar]

Si "Para llevar": aparece panel verde con campos opcionales:
    Nombre del cliente · Teléfono · Dirección
```

**Paso 1 — Seleccionar producto**
```
Clic en tarjeta → sin stock: bloqueada; con stock: abre configPanel
  Si Pollo Crudo → muestra selector de Corte directamente
  Si otro        → corteActual = {Unidad, mult:1} → habilitado directo
```

**Paso 2 — Corte (solo Pollo Crudo)**

| Botón | mult | corteKey | Cuartos descontados |
|---|---|---|---|
| ¼ Pierna-Pernil | 1 | cuarto | 1 |
| ¼ Pechuga-Ala | 1 | cuarto | 1 |
| Medio Pollo | 2 | medio | 2 |
| Pollo Entero | 4 | entero | 4 |

Precio: `PRECIOS_POLLO['cuarto' | 'medio' | 'entero']` (de configuración). Si es 0 → usa `precio_inventario × cuartos`.

**Pasos 3-7:** igual que versión anterior (cantidad, carrito, acompañamientos, registrar pedido, liquidar a caja).

### Empaque automático para llevar (Sprint 2)

Cuando `tipo_pedido = 'llevar'` y el empaque está activado en Config:
- El JS envía `primer_item: true` solo en el **primer ítem de cada orden**
- El servidor descuenta 1 unidad del artículo configurado como empaque
- El UPDATE es atómico (`WHERE cantidad > 0`) — no puede quedar negativo
- Si el empaque está en stock 0 → la venta continúa, el descuento se omite silenciosamente
- La tarjeta del artículo empaque se actualiza en tiempo real en el POS (si `empaque_id > 0` en la respuesta)

---

## 8. Módulo: Caja

**Vista:** `caja/index.php` — acceso: Autenticado (todos los roles)

### Pantalla `/caja`

1. Saldo actual — bloque blanco grande con `caja.total`
2. Mini-resumen del día — ingresos (verde) y retiros (rojo)
3. Banner de apertura (**Admin+** únicamente):
   - Sin apertura → ámbar "⚠️ Caja sin apertura" + CTA "Abrir Caja →"
   - Con apertura → verde "🔓 CAJA ABIERTA · Base: $X · Usuario · Hora"
4. Sección ALSÉ (**Admin+**) — formulario valor + motivo
5. Ventas sin liquidar — banner dorado si hay pendiente
6. Filtros rápidos de historial — Hoy · Ayer · Esta semana · Este mes · Todo
7. Formularios Añadir / Retirar — POST clásico con validación servidor
8. Tabla actividad de hoy — UNION historial_caja + ventas

> **Nota:** `$esAdmin` en CajaController usa `$rol->atLeast(Rol::Administrador)` — Jefe también ve el banner y la sección ALSÉ.

---

## 9. Módulo: Apertura de Caja

**Vista:** `caja/apertura.php` · **Acceso:** Admin+

- Una sola apertura por día (UNIQUE KEY en `fecha`)
- Si ya existe → muestra resumen de solo lectura con denominaciones y base
- Formulario: tabla de 10 denominaciones COP, subtotales en JS en tiempo real, observaciones
- POST calcula `base_inicial = SUM(denominacion × cantidad)`, inserta en `caja_aperturas` + `caja_apertura_denominaciones`

---

## 10. Módulo: Cierre de Caja

**Vista:** `caja/cierre.php` · **Acceso:** Admin+

### Fórmula del dinero esperado

```
dinero_esperado = base_inicial
                + ventas liquidadas del día
                + otras_entradas (ingresos manuales, excluye liquidaciones)
                − gastos_caja (retiros manuales del día)
                − creditos_empleados (entregados hoy)
                − alses (ALSÉS del día)
                − otras_salidas

sobrante = MAX(0, dinero_contado − dinero_esperado)
faltante  = MAX(0, dinero_esperado − dinero_contado)
```

- Requiere apertura del día; si no existe → redirige a `/caja/apertura`
- Un solo cierre por día (UNIQUE KEY en `fecha`)
- Vista en 3 secciones: movimientos precalculados (editables) / arqueo físico / resultado en tiempo real

---

## 11. Módulo: ALSÉS — Retiros de Seguridad

**Endpoint:** POST `/caja/alse` (JSON) · **Acceso:** Admin+

| Característica | Retiro normal | ALSÉ |
|---|---|---|
| Semántica | Gasto operativo | Traslado de seguridad |
| Afecta `caja.total` | ✅ | ✅ |
| Aparece en `historial_caja` | ✅ | ❌ |
| Aparece en `retiros_seguridad` | ❌ | ✅ |
| Cuenta como gasto en reportes | ✅ | ❌ |
| Línea en cierre de caja | `gastos_caja` | `alses` |

---

## 12. Módulo: Créditos a Empleados

**Vista:** `creditos/index.php` · **Acceso:** Admin+

### Estados

- `pendiente` → entregado, sin pagar
- `pagado` → cobrado (ingresó a caja)
- `vencido` → pasó `fecha_compromiso_pago` sin pagar

KPIs: Pendientes (cantidad) · Vencidos (cantidad, rojo) · Cartera total (suma pendiente+vencido, ámbar)

```
Crear:  sale de caja + INSERT creditos_empleados + INSERT historial_caja (retiro)
Pagar:  SELECT FOR UPDATE + verifica estado + entra a caja + INSERT historial_caja (ingreso)
Vencer: UPDATE estado='vencido' (manual o automático al cargar la vista)
```

**Auditoría:** crear y pagar quedan registrados en la tabla `auditoria`.

---

## 13. Módulo: Historial de Movimientos

**Vista:** `historial/index.php` · **Acceso:** Autenticado

`GET /historial[?desde=&hasta=&pagina=N]`

Contenido: solo movimientos de `historial_caja`. Tabla paginada (50/página) con resumen ingresos/retiros/neto del período.

---

## 14. Módulo: Inventario

**Vista:** `inventario/index.php` · **Acceso:** Autenticado

### Categorías ENUM

`Pollo Crudo` · `Papas` · `Acompañamientos` · `Salsas` · `Bebidas` · `Otros`

### Lógica especial Pollo Crudo

- Form → BD: `pollos × 4 = cuartos`; `costo_pollo ÷ 4 = costo_cuarto`
- BD → Form: `cuartos ÷ 4 = pollos`; `costo_cuarto × 4 = costo_pollo`
- BD → Tabla: `intdiv(cuartos, 4) pollos + (cuartos % 4) cuartos`

### Métodos nuevos en `Inventario` model

- `deductOne(int $id): bool` — descuenta 1 unidad atómicamente (`WHERE cantidad > 0`), retorna `false` si estaba en 0
- `countCritico(): int` — artículos con stock bajo (Pollo Crudo < 4 cuartos; otros ≤ 5 uds)

### Indicadores de stock bajo

- Pollo Crudo: rojo si < 4 cuartos (menos de 1 pollo entero)
- Otros: rojo si ≤ 5 unidades

**Auditoría:** crear, editar y eliminar artículos quedan registrados en `auditoria`.

---

## 15. Módulo: Usuarios

**Vista:** `usuarios/index.php` · **Acceso:** Jefe

### Roles disponibles en el dropdown

- **Jefe** (lila/púrpura) — acceso total
- **Administrador** (ámbar) — operativa sin config/usuarios/reportes
- **Empleado** (azul) — ventas, caja, inventario

### Badges de rol

| Rol | Fondo | Texto |
|---|---|---|
| Jefe | `#4a1942` | `#e879f9` (lila) |
| Administrador | `#78350f` | `#fde68a` (ámbar) |
| Empleado | `#1e3a5f` | `#bfdbfe` (azul) |

CRUD por modal AJAX. Eliminar con SweetAlert2. Link "Regresar" usa `$dashboardUrl` del rol activo.

**Auditoría:** crear, editar y eliminar usuarios quedan registrados en `auditoria`.

---

## 16. Módulo: Configuración

**Vista:** `config/index.php` · **Acceso:** Jefe

### Sección 1 — Precios de Pollo Asado

| Clave | Descripción |
|---|---|
| `precio_asado_cuarto` | Precio venta ¼ de pollo asado |
| `precio_asado_medio` | Precio venta ½ pollo asado |
| `precio_asado_entero` | Precio venta pollo entero asado |

Si precio = 0 → el POS usa fallback `precio_inventario × cuartos`.

### Sección 2 — Capacidad de condimentos

`condimentos_pollos_por_ciclo` (default 1000) — cuántos pollos alcanza una preparación de condimentos.

### Sección 3 — Empaque automático ← NUEVO

- Checkbox **"Activar descuento automático de empaque"** → `empaque_activo = '1'`
- Dropdown con todos los artículos del inventario → `empaque_inventario_id`
- Efecto: en cada pedido Para llevar, se descuenta 1 unidad del artículo seleccionado sin ningún clic extra del cajero

### Sección 4 — Estado del ciclo de condimentos

Barra de progreso + badge de estado + botón **"🔄 Reiniciar ciclo"**

**Auditoría:** cada precio que cambia genera un registro en `auditoria` con valor anterior y nuevo.

---

## 17. Módulo: Reportes Gerenciales

**Vistas:** `reportes/` · **Acceso:** Jefe · **CSV:** todos los reportes tienen botón 📥

### Menú `/reportes` — 5 tarjetas

Diario · Semanal · Mensual · Top Productos · Por Empleado

---

### Reporte Diario `/reportes/diario?fecha=YYYY-MM-DD`

KPIs: Ventas, Costo, Utilidad, Margen % · Pedidos por tipo (Local/Llevar)  
Movimientos del día: ingresos, gastos, créditos, ALSÉS  
Barras por hora · Top 10 productos · Navegación ← día anterior / día siguiente →  
**CSV:** hora, pedidos, ventas

---

### Reporte Semanal `/reportes/semanal?desde=&hasta=`

Accesos rápidos: Esta semana · Esta quincena · Semana pasada  
KPIs: Ventas, Utilidad, Margen, Pedidos, Promedio/día, Días con ventas  
Mejor y menor día · Barras por día · Top 10 productos  
**CSV:** fecha, pedidos, ventas

---

### Reporte Mensual `/reportes/mensual?mes=YYYY-MM`

Navegación ← mes anterior / mes siguiente →  
KPIs: Ventas, Costo, Utilidad, Gastos, Créditos, Pedidos  
Resumen financiero: `+ Ventas + Ingresos manuales − Gastos − Créditos − ALSÉS = Flujo neto`  
Barras por día del mes · Top 10 productos  
**CSV:** fecha, pedidos, ventas

---

### Top Productos `/reportes/productos?desde=&hasta=`

Accesos rápidos: Hoy · Este mes · Este año · Todo el tiempo  
Ranking con 🥇🥈🥉 · Columnas: #, Producto, Categoría, Pedidos, Uds vendidas (barra), Ingresos, Costo, Margen  
**CSV:** #, producto, categoría, pedidos, uds, ingresos, costo, margen

---

### Por Empleado `/reportes/empleados?desde=&hasta=&usuario=` ← NUEVO

Accesos rápidos: Hoy · Esta semana · Este mes  
Tabla ranking: empleado (con barra proporcional), pedidos, ventas, ticket promedio, 🏠 local, 🛵 llevar, días activo  
Clic en "📊 Ver" → detalle de ese empleado con barras por día en el período  
**CSV:** #, empleado, pedidos, ventas, ticket promedio, local, para llevar, días activo

---

### Exportación CSV (todos los reportes)

- Parámetro `?export=csv` en la URL de cualquier reporte
- Botón **📥 CSV** visible en el pie de cada reporte
- CSV con BOM UTF-8 (compatible con Excel en Windows)
- Separador `;` (compatible con Excel Colombia/ES)
- Nombre de archivo: `tipo_periodo_fechaActual.csv`
- Sin dependencias externas — PHP nativo `fputcsv`

---

## 18. Módulo: Auditoría Operativa ← NUEVO

**Vista:** `auditoria/index.php` · **Acceso:** Jefe  
**Ruta:** `GET /auditoria[?modulo=&usuario=&pagina=N]`

### Qué se registra automáticamente

| Módulo | Acciones registradas | Descripción ejemplo |
|---|---|---|
| `inventario` | crear, editar, eliminar | "Artículo creado: Papas Grandes (Papas)" |
| `usuarios` | crear, editar, eliminar | "Usuario creado: cajero1 (Rol: Empleado)" |
| `config` | editar (solo precios) | "Precio ¼ Asado: $8.000 → $9.500" |
| `creditos` | crear, pagar | "Crédito $50.000 a empleado id=3" |

### Pantalla

- Filtro por módulo (dropdown de módulos activos en BD) y por usuario (texto)
- Tabla con badges de acción: crear=verde, editar=ámbar, eliminar=rojo, pagar=azul
- Paginación 100 registros/página
- Acceso desde el dashboard del Jefe (botón 🔍 AUDITORÍA)

---

## 19. Seguridad transversal

| Mecanismo | Implementación |
|---|---|
| **CSRF** | Token en sesión, meta tag + `_token` en forms, header `X-CSRF-Token` en AJAX |
| **Autenticación** | `AuthMiddleware::handle()` en todos los controladores |
| **Autorización jerárquica** | `Rol::atLeast()` + `RoleMiddleware::require(Rol::X)` — roles superiores heredan permisos |
| **Contraseñas** | `password_hash()` / `password_verify()` con bcrypt |
| **XSS** | `View::escape()` = `htmlspecialchars(ENT_QUOTES|ENT_SUBSTITUTE)` en toda salida de BD |
| **Inyección SQL** | PDO con prepared statements en todos los modelos |
| **Session fixation** | `Session::regenerate()` tras login exitoso |
| **Stock concurrente** | `SELECT ... FOR UPDATE` dentro de transacción en `Venta::store()` |
| **Empaque atómico** | `UPDATE inventario SET cantidad = cantidad - 1 WHERE id = ? AND cantidad > 0` |
| **Doble pago crédito** | `SELECT ... FOR UPDATE` + verificación de estado en `CreditoEmpleado::pagar()` |
| **Doble apertura** | `UNIQUE KEY uq_apertura_fecha` en `caja_aperturas` |
| **Doble cierre** | `UNIQUE KEY uq_cierre_fecha` en `caja_cierres` |
| **Redirect abierto** | `Response::redirect()` solo acepta rutas internas que empiecen con `/` |
| **Datos sensibles** | `.env` ignorado en git; credenciales de BD fuera del código |

---

## 20. Checklist de verificación

### Autenticación
- [ ] Login correcto redirige: Jefe → `/dashboard/jefe`, Admin → `/dashboard`, Empleado → `/dashboard/empleado`
- [ ] Login incorrecto muestra toast de error sin revelar qué campo falló
- [ ] Logout destruye sesión y redirige a `/`
- [ ] Ruta protegida sin sesión redirige a `/`

### Roles y permisos
- [ ] Empleado en `/usuarios`, `/config`, `/reportes`, `/creditos`, `/auditoria` → redirigido
- [ ] Administrador en `/usuarios`, `/config`, `/reportes`, `/auditoria` → redirigido
- [ ] Jefe accede a todos los módulos sin restricción
- [ ] Jefe en `/caja/apertura`, `/caja/cierre`, `/creditos` → acceso permitido (hereda Admin+)
- [ ] Badge de rol correcto: Jefe=lila, Admin=ámbar, Empleado=azul

### Dashboard del Jefe
- [ ] KPIs del día visibles: ventas, utilidad, caja, pedidos
- [ ] Panel de alertas aparece solo cuando hay algo relevante
- [ ] Link a auditoría visible (🔍 AUDITORÍA)
- [ ] KPIs del mes correctos con ticket promedio
- [ ] Logout accesible

### Dashboard del Administrador
- [ ] Ve 6 botones: Inventario, Caja, Ventas, Apertura, Cierre, Créditos
- [ ] NO ve botones de Usuarios, Config, Reportes, Auditoría
- [ ] Alerta de condimentos visible (ámbar/rojo) cuando corresponde

### Dashboard del Empleado
- [ ] Ve solo 3 botones: Inventario, Caja, Ventas
- [ ] NO ve ningún botón administrativo

### Ventas / POS (post-eliminación Broaster)
- [ ] NO aparece selector "Asado / Broaster" en ningún lugar
- [ ] Al seleccionar Pollo Crudo → aparece directamente selector de Corte (sin paso intermedio)
- [ ] 4 botones de corte: ¼ Pierna, ¼ Pechuga, ½ Pollo, Pollo Entero
- [ ] Nombre en carrito: "Asado — ¼ Pierna-Pernil" etc.
- [ ] Precio usa PRECIOS_POLLO['cuarto'/'medio'/'entero'] (estructura plana, sin clave 'Asado')
- [ ] Selector tipo pedido 🏠 Local / 🛵 Para llevar funcional
- [ ] Panel de cliente aparece solo con "Para llevar"
- [ ] Empaque automático descuenta si está activado y tipo=llevar (primer ítem)
- [ ] Stock del artículo empaque se actualiza en tiempo real en el POS
- [ ] Liquidar a caja funciona correctamente

### Empaque automático
- [ ] Checkbox en Config activa/desactiva el descuento
- [ ] Dropdown muestra todos los artículos del inventario con stock actual
- [ ] Sin empaque configurado (id=0) → venta normal sin descuento
- [ ] Con empaque en stock 0 → venta continúa sin error

### Caja
- [ ] Admin Y Jefe ven banner de apertura y sección ALSÉ
- [ ] Empleado NO ve banner ni ALSÉ
- [ ] Añadir/retirar dinero funciona
- [ ] ALSÉ descuenta caja y va a `retiros_seguridad` (no a `historial_caja`)

### Apertura / Cierre
- [ ] Admin y Jefe pueden abrir y cerrar caja
- [ ] Segunda apertura el mismo día → error
- [ ] Cierre sin apertura → redirige a /caja/apertura con mensaje
- [ ] Fórmula del cierre incluye base + ventas + entradas − gastos − créditos − alsés

### Créditos
- [ ] Admin y Jefe pueden crear y pagar créditos
- [ ] Crédito nuevo aparece en auditoría
- [ ] Pago registrado aparece en auditoría

### Inventario
- [ ] Crear artículo → aparece en tabla + registro en auditoría
- [ ] Editar artículo → cambios guardados + registro en auditoría
- [ ] Eliminar artículo → pide confirmación + registro en auditoría
- [ ] Pollo Crudo: conversión pollos ↔ cuartos correcta en form y tabla

### Usuarios
- [ ] Crear usuario → tabla cargada + registro en auditoría
- [ ] Editar usuario → cambios guardados + registro en auditoría
- [ ] Eliminar usuario → SweetAlert2 + registro en auditoría
- [ ] Dropdown de rol muestra las 3 opciones: Jefe, Administrador, Empleado

### Configuración
- [ ] Precios Broaster NO aparecen (eliminados)
- [ ] Los 3 precios de Pollo Asado se guardan y persisten
- [ ] Cambio de precio → registro en auditoría con valor anterior → nuevo
- [ ] Sección empaque automático visible con checkbox y dropdown
- [ ] Reiniciar ciclo de condimentos → contador vuelve a 0

### Reportes Gerenciales
- [ ] Menú de reportes muestra 5 tarjetas (incluye Por Empleado)
- [ ] Diario: KPIs, barras por hora, top productos, navegación de días
- [ ] Semanal: rango libre, mejor y menor día, barras por día
- [ ] Mensual: navegación entre meses, resumen financiero completo
- [ ] Top Productos: ranking con podio 🥇🥈🥉
- [ ] Por Empleado: ranking con barra proporcional, drill-down por día
- [ ] Botón 📥 CSV visible en todos los reportes
- [ ] CSV descargado compatible con Excel (BOM UTF-8, separador `;`)

### Auditoría
- [ ] Accesible solo para Jefe (Admin y Empleado redirigidos)
- [ ] Tabla vacía si aún no se realizaron acciones auditadas
- [ ] Filtro por módulo y por usuario funciona
- [ ] Badges de acción con colores correctos (verde/ámbar/rojo/azul)
- [ ] Paginación funciona correctamente

### Seguridad
- [ ] Todo POST tiene campo `_token` oculto
- [ ] Todo AJAX incluye header `X-CSRF-Token`
- [ ] Token inválido → 403
- [ ] `<script>alert(1)</script>` en campos → escapa correctamente
- [ ] Acceso a ruta Jefe como Admin → redirige
- [ ] Acceso a ruta Admin+ como Empleado → redirige

---

*Sistema Kokoro Pollo v3.0 · Documentado el 2026-06-01*

**Historial de sprints:**
- v1.0: Autenticación, Inventario, Caja, Ventas POS, Historial, Usuarios, Configuración
- v2.0: Apertura, Cierre, Créditos, ALSÉS, Para Llevar, Condimentos, Reportes (Diario/Semanal/Mensual/Productos)
- v3.0 — **Sprint 1:** Rol Jefe (jerarquía 3 niveles), Eliminación Broaster, 3 dashboards diferenciados
- v3.0 — **Sprint 2:** Ventas por Empleado, Empaques automáticos
- v3.0 — **Sprint 3:** Auditoría operativa, Exportación CSV en 5 reportes

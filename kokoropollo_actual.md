# Kokoro Pollo — Documentación Completa del Sistema

> Sistema POS especializado para asadero de pollo tradicional colombiano.  
> Stack: **PHP 8.4** · **MySQL** (InnoDB) · **Tailwind CSS CDN** · **Vanilla JS**  
> Servidor local: `http://kokoropollo.test` vía **Laravel Herd**  
> Última actualización: 2026-06-01

---

## Índice

1. [Arquitectura general](#1-arquitectura-general)
2. [Base de datos — 12 tablas](#2-base-de-datos--12-tablas)
3. [Control de acceso por rol](#3-control-de-acceso-por-rol)
4. [Mapa de rutas](#4-mapa-de-rutas)
5. [Módulo: Autenticación](#5-módulo-autenticación)
6. [Módulo: Dashboard](#6-módulo-dashboard)
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
18. [Seguridad transversal](#18-seguridad-transversal)
19. [Checklist de verificación](#19-checklist-de-verificación)

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

### Estructura de archivos relevante

```
KokoroPollo/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── VentasController.php
│   │   ├── CajaController.php
│   │   ├── CajaAperturaController.php     ← Sprint 1
│   │   ├── CajaCierreController.php       ← Sprint 1
│   │   ├── AlsesController.php            ← Sprint 1
│   │   ├── CreditosController.php         ← Sprint 1
│   │   ├── HistorialController.php
│   │   ├── InventarioController.php
│   │   ├── UsuariosController.php
│   │   ├── ConfigController.php
│   │   └── ReportesController.php         ← Sprint 3
│   ├── Models/
│   │   ├── Caja.php
│   │   ├── CajaApertura.php               ← Sprint 1
│   │   ├── CajaCierre.php                 ← Sprint 1
│   │   ├── CreditoEmpleado.php            ← Sprint 1
│   │   ├── RetiroSeguridad.php            ← Sprint 1
│   │   ├── HistorialCaja.php
│   │   ├── Inventario.php
│   │   ├── Venta.php
│   │   ├── Configuracion.php
│   │   └── Reporte.php                    ← Sprint 3
│   ├── Core/  (Csrf, Database, Logger, Request, Response, Router, Session, View)
│   ├── Enums/ (Rol)
│   └── Middleware/ (AuthMiddleware, RoleMiddleware)
├── config/routes.php
├── database/
│   ├── schema.sql
│   └── migrations/
│       ├── 001_caja_aperturas.sql
│       ├── 002_caja_cierres.sql
│       ├── 003_creditos_empleados.sql
│       ├── 004_retiros_seguridad.sql
│       └── 005_ventas_tipo_pedido.sql
├── public/
│   ├── index.php
│   ├── css/styles.css
│   ├── js/ventas.js
│   └── img/logo.png
└── resources/views/
    ├── auth/login.php
    ├── dashboard/index.php
    ├── ventas/index.php
    ├── caja/index.php
    ├── caja/apertura.php                  ← Sprint 1
    ├── caja/cierre.php                    ← Sprint 1
    ├── creditos/index.php                 ← Sprint 1
    ├── historial/index.php
    ├── inventario/index.php
    ├── usuarios/index.php
    ├── config/index.php
    ├── reportes/                          ← Sprint 3
    │   ├── index.php
    │   ├── diario.php
    │   ├── semanal.php
    │   ├── mensual.php
    │   └── productos.php
    └── partials/
        ├── head.php
        ├── toasts.php
        └── confirm-modal.php
```

### Identidad visual

- **Fondo:** `linear-gradient(135deg, #3b0a0a 0%, #4a0e0e 40%, #2b1a1a 100%)` aplicado a todas las páginas
- **Acento principal:** Oro `#d4af37` / `#e6c857`
- **Cards:** `#3c1f1f` (rojo oscuro medio)
- **Tipografía:** Tailwind CDN (sistema)
- **CSS personalizado:** `public/css/styles.css` — botones, inputs, filas de tabla, toasts, scrollbar

---

## 2. Base de datos — 12 tablas

Base de datos: `kokoropollo` · Motor: InnoDB · Charset: utf8mb4

### Tablas originales (v1)

| Tabla | Descripción | Campos clave |
|---|---|---|
| `usuarios` | Cuentas del sistema | id, nombre, usuario (UNIQUE), clave (bcrypt), rol ENUM(Administrador/Empleado) |
| `inventario` | Artículos en stock | id, articulo, categoria ENUM, cantidad (cuartos), valor (por cuarto) |
| `caja` | Saldo actual (fila única id=1) | id, total DECIMAL |
| `historial_caja` | Movimientos manuales de caja | id, tipo (ingreso/retiro), valor, concepto, usuario, fecha |
| `ventas` | Líneas de venta registradas | id, orden_id, inventario_id (FK), cantidad, precio_unitario, total, usuario, **tipo_pedido, nombre_cliente, telefono, direccion**, fecha, liquidado |
| `configuracion` | Parámetros clave-valor | clave (PK), valor |

### Tablas nuevas (v2 — Sprints 1-3)

| Tabla | Sprint | Descripción |
|---|---|---|
| `caja_aperturas` | 1 | Una apertura por día con base inicial |
| `caja_apertura_denominaciones` | 1 | Detalle de billetes/monedas en la apertura |
| `caja_cierres` | 1 | Cierre diario con arqueo y resultado |
| `caja_cierre_denominaciones` | 1 | Detalle del conteo físico en el cierre |
| `creditos_empleados` | 1 | Préstamos internos a empleados |
| `retiros_seguridad` | 1 | ALSÉS — traslados de seguridad |

### Columnas nuevas en `ventas` (Sprint 2 — Migración 005)

```sql
tipo_pedido    ENUM('local','llevar') NOT NULL DEFAULT 'local'
nombre_cliente VARCHAR(100) DEFAULT NULL
telefono       VARCHAR(20)  DEFAULT NULL
direccion      VARCHAR(255) DEFAULT NULL
```

### Claves nuevas en `configuracion`

```
condimentos_cuartos_offset    → Cuartos vendidos al iniciar ciclo actual (base del contador)
condimentos_pollos_por_ciclo  → Capacidad de la preparación (default 1000)
precio_asado_cuarto/medio/entero
precio_broaster_cuarto/medio/entero
```

### Regla especial Pollo Crudo

- Unidad interna: **cuarto** (¼ de pollo)
- 1 pollo = 4 cuartos en BD
- El inventario almacena y descuenta cuartos
- La UI muestra "X pollos + Y cuartos"
- El formulario de inventario convierte: pollos → ×4 cuartos, costo/pollo → ÷4 costo/cuarto
- En ventas: el corte determina cuántos cuartos se descuentan (¼=1, ½=2, entero=4)

### Denominaciones soportadas (COP)
`100.000` · `50.000` · `20.000` · `10.000` · `5.000` · `2.000` · `1.000` · `500` · `200` · `100`

---

## 3. Control de acceso por rol

| Módulo | Administrador | Empleado |
|---|---|---|
| Dashboard | Panel con 9 botones | Panel con 3 botones |
| Ventas | ✅ | ✅ |
| Caja | ✅ | ✅ |
| Historial | ✅ | ✅ |
| Inventario | ✅ | ✅ |
| Apertura de Caja | ✅ | ❌ |
| Cierre de Caja | ✅ | ❌ |
| ALSÉS | ✅ (widget en Caja) | ❌ |
| Créditos Empleados | ✅ | ❌ |
| Configuración | ✅ | ❌ |
| Reportes | ✅ | ❌ |
| Usuarios | ✅ | ❌ |

**Sesión:** guarda `usuario_id`, `usuario`, `rol`  
**Middleware:** `AuthMiddleware::handle()` (sesión activa) · `RoleMiddleware::require(Rol::X)` (rol específico)

---

## 4. Mapa de rutas

| Método | Ruta | Controlador::método | Acceso |
|---|---|---|---|
| GET | `/` | AuthController::showLogin | Público |
| POST | `/login` | AuthController::login | Público |
| GET | `/logout` | AuthController::logout | Autenticado |
| GET | `/dashboard` | DashboardController::index | Autenticado |
| GET | `/dashboard/empleado` | DashboardController::index | Autenticado |
| GET | `/inventario` | InventarioController::index | Autenticado |
| POST | `/inventario/store` | InventarioController::store | Autenticado |
| POST | `/inventario/update` | InventarioController::update | Autenticado |
| POST | `/inventario/delete` | InventarioController::destroy | Autenticado |
| GET | `/caja` | CajaController::index | Autenticado |
| POST | `/caja` | CajaController::process | Autenticado |
| GET | `/caja/resumen` | CajaController::resumen | Autenticado (JSON) |
| POST | `/caja/ajuste` | CajaController::ajuste | Autenticado (JSON) |
| GET | `/caja/apertura` | CajaAperturaController::index | Admin |
| POST | `/caja/apertura` | CajaAperturaController::store | Admin |
| GET | `/caja/cierre` | CajaCierreController::index | Admin |
| POST | `/caja/cierre` | CajaCierreController::store | Admin |
| POST | `/caja/alse` | AlsesController::store | Admin (JSON) |
| GET | `/creditos` | CreditosController::index | Admin |
| POST | `/creditos/crear` | CreditosController::crear | Admin (JSON) |
| POST | `/creditos/pagar` | CreditosController::pagar | Admin (JSON) |
| POST | `/creditos/vencer` | CreditosController::vencer | Admin (JSON) |
| GET | `/historial` | HistorialController::index | Autenticado |
| GET | `/ventas` | VentasController::index | Autenticado |
| POST | `/ventas/store` | VentasController::store | Autenticado (JSON) |
| POST | `/ventas/liquidar` | VentasController::liquidar | Autenticado (JSON) |
| GET | `/config` | ConfigController::index | Admin |
| POST | `/config` | ConfigController::save | Admin |
| POST | `/config/reset-condimentos` | ConfigController::resetCondimentos | Admin |
| GET | `/reportes` | ReportesController::index | Admin |
| GET | `/reportes/diario` | ReportesController::diario | Admin |
| GET | `/reportes/semanal` | ReportesController::semanal | Admin |
| GET | `/reportes/mensual` | ReportesController::mensual | Admin |
| GET | `/reportes/productos` | ReportesController::productos | Admin |
| GET | `/usuarios` | UsuariosController::index | Admin |
| GET | `/usuarios/list` | UsuariosController::list | Admin (JSON) |
| POST | `/usuarios/create` | UsuariosController::create | Admin (JSON) |
| POST | `/usuarios/update` | UsuariosController::update | Admin (JSON) |
| POST | `/usuarios/delete` | UsuariosController::destroy | Admin (JSON) |

---

## 5. Módulo: Autenticación

**Vistas:** `auth/login.php`

### Flujo Login
```
GET /  →  muestra formulario con logo, campos usuario/contraseña, btn Ingresar

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
        redirect según rol → /dashboard o /dashboard/empleado
```

### Flujo Logout
```
GET /logout → session_destroy() → redirect('/')
```

---

## 6. Módulo: Dashboard

**Vista:** `dashboard/index.php`

### Datos calculados en el controlador
- `$totalDia` — suma de `ventas.total` del día (se muestra bajo el botón VENTAS)
- `$pollosEnCiclo` — pollos vendidos desde el último reinicio de condimentos
- `$pctCondimentos` — porcentaje del ciclo consumido
- `$alertaCondimentos` — `null | 'preventiva' | 'critica' | 'agotado'`

### Widget de alerta de condimentos (sobre los botones)
| % del ciclo | Estilo | Mensaje |
|---|---|---|
| < 50% | Sin alerta | — |
| 50-79% | Borde ámbar | ⚠️ Preparar condimentos — N de 1000 pollos |
| 80-99% | Borde rojo | 🔴 Condimentos críticos |
| ≥ 100% | Fondo rojo intenso | 🚨 CONDIMENTOS AGOTADOS |

### Botones por rol

**Administrador (9 botones):**
📦 INVENTARIO · 👥 USUARIOS · ⚙️ CONFIG · 💰 CAJA · 🔓 APERTURA · 🔒 CIERRE · 💳 CRÉDITOS · 📊 REPORTES · 🛒 VENTAS

**Empleado (3 botones):**
📦 INVENTARIO · 💰 CAJA · 🛒 VENTAS

---

## 7. Módulo: Ventas (POS)

**Vistas:** `ventas/index.php` · **JS:** `public/js/ventas.js`

### Layout pantalla dividida
- **Columna izquierda (2/5):** Panel de Caja — saldo, movimientos, ajustes, ALSÉ
- **Columna derecha (3/5):** POS de ventas — selector tipo pedido, productos, configurador, carrito

### Flujo completo de una venta

**Paso 0 — Tipo de pedido** (selector persistente para toda la sesión)
```
[🏠 Local]  [🛵 Para llevar]

Si "Para llevar": aparece panel verde con campos opcionales:
    Nombre del cliente · Teléfono · Dirección
```

**Paso 1 — Seleccionar producto**
```
Clic en tarjeta de producto
  ├─► Sin stock → bloqueado (opacity .4, cursor not-allowed)
  └─► Con stock → abre configPanel con nombre, precio, stock
        Si Pollo Crudo → muestra selector Preparación + Corte
        Si otro → corteActual = {Unidad, mult:1} → habilitado directo
```

**Paso 2 — Configurar Pollo Crudo** (solo aplica)

| Preparación | Corte | mult | corteKey | Cuartos descontados |
|---|---|---|---|---|
| Asado / Broaster | ¼ Pierna-Pernil | 1 | cuarto | 1 |
| Asado / Broaster | ¼ Pechuga-Ala | 1 | cuarto | 1 |
| Asado / Broaster | Medio Pollo | 2 | medio | 2 |
| Asado / Broaster | Pollo Entero | 4 | entero | 4 |

**Cálculo de precio:**
```
Si precio_configurado (tabla config) > 0:
    subtotal = precioCorte × cantidad_pedida
    precio_servidor = precioCorte / mult

Si precio = 0 (sin configurar):
    subtotal = precio_por_cuarto × (cantidad × mult)
    precio_servidor = precio_por_cuarto
```

**Paso 3 — Ajustar cantidad**
- Botones `−` / `+` o input directo
- Alerta roja si `cantidad × mult > stock disponible` (incluyendo lo ya en carrito)

**Paso 4 — Agregar al carrito**
```
agregarAlCarrito()
  ├─► push al array carrito con: uid, id, nombre, corte, preparacion,
  │   cantForm, cantInv, precio, subtotal, costoUnit, costoSubtotal, margenSubtotal
  │
  ├─► Si Pollo Crudo → mostrarAcomp() (acompañamientos rápidos)
  └─► Si otro → renderCarrito()
```

**Paso 4b — Acompañamientos** (solo tras agregar Pollo Crudo)
```
Panel verde con productos de: Papas, Acompañamientos, Salsas, Bebidas (stock > 0)
  ├─► Toggle visual por ítem (borde dorado = seleccionado)
  ├─► "✅ Listo" → agrega seleccionados (1 ud c/u, precio inventario)
  └─► "✕ Saltar" → cierra sin agregar
```

**Paso 5 — Carrito**
- Lista de ítems con nombre, preparación/corte, cantidad, precio
- Costo de inventario + margen por ítem
- Resumen: Venta | Costo | Margen
- Botón `×` para eliminar ítem individual
- "🗑️ Vaciar" para limpiar todo

**Paso 6 — Registrar pedido**
```
registrarPedido()
  ├─► Genera orden_id aleatorio (alfanum 10 chars)
  ├─► Para cada ítem:
  │     POST /ventas/store (JSON) con:
  │       orden_id, inventario_id, cantidad(cuartos), precio_unitario,
  │       tipo_pedido, nombre_cliente, telefono, direccion
  │
  │     Servidor (transacción MySQL):
  │       SELECT cantidad FOR UPDATE (bloqueo de fila)
  │       Verifica stock >= cantidad
  │       UPDATE inventario SET cantidad = cantidad - ?
  │       INSERT INTO ventas (todos los campos)
  │       COMMIT
  │
  ├─► Si error de stock → ítem queda en carrito, alerta visible
  ├─► Si OK → actualiza stock en tarjeta UI en tiempo real
  └─► Carrito vacío → historial visual + panel liquidación incrementa
```

**Paso 7 — Liquidar a caja**
```
POST /ventas/liquidar
  ├─► SUM(total) WHERE liquidado = 0
  ├─► UPDATE caja SET total = total + pendiente
  ├─► INSERT historial_caja (tipo='ingreso', concepto='Liquidación: N venta(s)')
  └─► UPDATE ventas SET liquidado = 1 WHERE liquidado = 0
```

**Factura del día**
- Modal con todos los pedidos registrados en la sesión (memoria JS)
- Muestra tipo (🏠/🛵) por pedido
- Botón "🖨️ Imprimir" abre ventana nueva con `window.print()`

### Panel de Caja (izquierda en Ventas)
- Saldo en tiempo real
- Mini-resumen ingresos/retiros del día
- Formularios AJAX para Añadir y Retirar (POST `/caja/ajuste`)
- Tabla de actividad unificada (caja + ventas del día)
- **Si Admin:** sección ALSÉ con campo valor + motivo (POST `/caja/alse`)
- Enlace "📋 Gestión completa de caja →"

---

## 8. Módulo: Caja

**Vista:** `caja/index.php`

### Pantalla principal `/caja`

**Elementos:**
1. **Saldo actual** — bloque blanco grande con `caja.total`
2. **Mini-resumen del día** — ingresos (verde) y retiros (rojo) de `historial_caja`
3. **Banner de apertura** (solo Admin):
   - Sin apertura → banner ámbar "⚠️ Caja sin apertura" + CTA "Abrir Caja →"
   - Con apertura → banner verde "🔓 CAJA ABIERTA · Base: $X · Usuario · Hora"
4. **Sección ALSÉ** (solo Admin) — formulario valor + motivo
5. **Ventas sin liquidar** — banner dorado si hay pendiente, con link a Ventas
6. **Filtros rápidos de historial** — Hoy · Ayer · Esta semana · Este mes · Todo
7. **Formularios Añadir / Retirar** — POST form clásico con validación servidor
8. **Tabla actividad de hoy** — UNION de `historial_caja` + `ventas` del día

### Flujo Añadir dinero
```
POST /caja (accion=anadir)
  ├─► Valida valor > 0 y concepto
  ├─► UPDATE caja SET total = total + valor
  ├─► INSERT historial_caja (tipo='ingreso')
  └─► flash('exito') + redirect('/caja')
```

### Flujo Retirar dinero
```
POST /caja (accion=retirar)
  ├─► Valida valor > 0 y concepto
  ├─► Verifica total >= valor
  ├─► UPDATE caja SET total = total - valor
  ├─► INSERT historial_caja (tipo='retiro')
  └─► flash('exito') + redirect('/caja')
```

### GET /caja/resumen (JSON)
Usado por Ventas para actualizar el panel de caja en tiempo real.
Devuelve: `{ total, ingresosHoy, retirosHoy, ventasPendientes, movimientos[] }`

---

## 9. Módulo: Apertura de Caja

**Vista:** `caja/apertura.php` · **Solo Admin**

### Reglas
- Una sola apertura permitida por día (UNIQUE KEY en `fecha`)
- Solo Administrador puede registrarla
- Si ya existe apertura del día → muestra resumen de solo lectura

### Flujo registrar apertura
```
GET /caja/apertura
  └─► Si existe apertura hoy → muestra resumen (tabla de denominaciones + base)
      Si no existe → muestra formulario de conteo

POST /caja/apertura
  ├─► Valida CSRF y que no exista apertura hoy
  ├─► Calcula base_inicial = SUM(denominacion × cantidad)
  ├─► Transacción:
  │     INSERT caja_aperturas (fecha, usuario_id, base_inicial, observaciones)
  │     INSERT caja_apertura_denominaciones (una fila por denominación > 0)
  └─► flash('exito') + redirect('/caja/apertura')
```

### Vista formulario
- Tabla con las 10 denominaciones COP
- Input de cantidad por denominación → subtotal calculado en JS en tiempo real
- Total de la base visible en el pie de la tabla
- Campo de observaciones opcional
- Botón "🔓 Registrar Apertura"

### Integración con Caja
`CajaController::index()` consulta `CajaApertura::getHoy()` y pasa `$aperturaHoy` a la vista de caja, que muestra el banner correspondiente.

---

## 10. Módulo: Cierre de Caja

**Vista:** `caja/cierre.php` · **Solo Admin**

### Reglas
- Requiere que exista apertura del día
- Un solo cierre por día (UNIQUE KEY en `fecha`)
- No permite editar un cierre ya registrado

### Fórmula del dinero esperado
```
dinero_esperado = base_inicial
                + ventas (liquidadas del día)
                + otras_entradas (ingresos manuales, no liquidaciones)
                − gastos_caja (retiros manuales del día)
                − creditos_empleados (créditos entregados hoy)
                − alses (ALSÉS del día)
                − otras_salidas

sobrante = MAX(0, dinero_contado − dinero_esperado)
faltante  = MAX(0, dinero_esperado − dinero_contado)
```

### Flujo
```
GET /caja/cierre
  ├─► Sin apertura del día → redirect /caja/apertura con mensaje
  ├─► Ya existe cierre → muestra resumen de solo lectura con sobrante/faltante
  └─► Sin cierre → muestra formulario con 3 secciones:
        Sección 1: Movimientos del día (precalculados, editables)
        Sección 2: Arqueo físico (tabla de denominaciones)
        Sección 3: Resultado en tiempo real (esperado vs contado)

POST /caja/cierre
  ├─► Valida CSRF, apertura del día y que no exista cierre
  ├─► Recibe denominaciones del conteo físico
  ├─► Calcula dinero_contado = SUM(denominacion × cantidad)
  ├─► Recupera base_inicial de la apertura
  ├─► Calcula dinero_esperado con fórmula
  ├─► Calcula sobrante / faltante
  └─► Transacción: INSERT caja_cierres + caja_cierre_denominaciones
```

### Vista resultado del cierre
- ✅ Verde si cuadre exacto
- 🟢 Verde + monto si sobrante
- 🔴 Rojo + monto si faltante

---

## 11. Módulo: ALSÉS — Retiros de Seguridad

**Endpoint:** POST `/caja/alse` (JSON) · **Solo Admin**

### Qué es un ALSÉ
Un **ALSÉ** es un retiro de efectivo por razones de seguridad cuando hay demasiado dinero en caja. Se diferencia de un retiro normal en que:

| Característica | Retiro normal | ALSÉ |
|---|---|---|
| Semántica | Gasto operativo | Traslado de seguridad |
| Afecta `caja.total` | ✅ Sí | ✅ Sí |
| Aparece en `historial_caja` | ✅ Sí | ❌ No |
| Aparece en `retiros_seguridad` | ❌ No | ✅ Sí |
| Cuenta como gasto en reportes | ✅ Sí | ❌ No |
| Se incluye en cierre de caja | Línea "gastos_caja" | Línea "alses" |

### Flujo
```
POST /caja/alse { valor, motivo } (JSON con header X-CSRF-Token)
  ├─► Valida Admin, CSRF, valor > 0, motivo no vacío
  ├─► Verifica caja.total >= valor
  ├─► Transacción:
  │     INSERT retiros_seguridad (valor, motivo, usuario_id)
  │     UPDATE caja SET total = total - valor
  └─► JSON { status: 'ok', nuevoCajaTotal }
```

### Integración en la vista de Caja
Sección visible solo para admin con borde ámbar, formulario inline (valor + motivo), botón "🔒 ALSÉ". Tras registrar, recarga la página.

---

## 12. Módulo: Créditos a Empleados

**Vista:** `creditos/index.php` · **Solo Admin**

### Qué es un crédito
Préstamo interno a un empleado. Sale de caja, se espera recuperar. No es una venta fiada ni un gasto operativo.

### Estados del crédito
- `pendiente` — entregado, sin pagar
- `pagado` — cobrado (ingresó a caja)
- `vencido` — pasó la fecha de compromiso de pago sin pagar

La vista actualiza automáticamente vencidos al cargar (`actualizarVencidos()` compara `fecha_compromiso_pago < CURDATE()`).

### KPIs en la pantalla
- Pendientes (cantidad)
- Vencidos (cantidad, fondo rojo)
- Cartera total (suma pendiente + vencido, fondo ámbar)

### Flujo crear crédito
```
POST /creditos/crear (JSON)
  ├─► Valida: empleado_id existe, valor > 0, fecha_compromiso válida
  ├─► Transacción:
  │     Verifica caja.total >= valor
  │     INSERT creditos_empleados (estado='pendiente')
  │     UPDATE caja SET total = total - valor
  │     INSERT historial_caja (tipo='retiro', concepto='Crédito empleado: {nombre}')
  └─► JSON { status: 'ok' }
```

### Flujo pagar crédito
```
POST /creditos/pagar { id } (JSON)
  ├─► SELECT ... FOR UPDATE (anti doble pago)
  ├─► Verifica estado = 'pendiente' o 'vencido'
  ├─► Transacción:
  │     UPDATE creditos_empleados SET estado='pagado', fecha_pago=CURDATE()
  │     UPDATE caja SET total = total + valor
  │     INSERT historial_caja (tipo='ingreso', concepto='Pago crédito: {nombre}')
  └─► JSON { status: 'ok' }
```

### Badge de estado en tabla
- `pendiente` → fondo ámbar oscuro · ⏳
- `vencido` → fondo rojo oscuro · 🔴
- `pagado` → fondo verde oscuro · ✅

---

## 13. Módulo: Historial de Movimientos

**Vista:** `historial/index.php`

### Ruta
`GET /historial[?desde=YYYY-MM-DD&hasta=YYYY-MM-DD&pagina=N]`

### Contenido
El historial muestra **únicamente movimientos de `historial_caja`** (ingresos y retiros manuales + liquidaciones de ventas). Los ALSÉS van a tabla separada y las ventas individuales se ven en el POS.

### Pantalla
1. **Filtro de fechas** — Desde / Hasta → recarga con GET
2. **Resumen del período** (cuando hay registros):
   - Verde: total ingresos
   - Rojo: total retiros
   - Oscuro: neto (ingresos − retiros)
3. **Tabla paginada** (50 registros/página):
   - ID · Fecha y hora · Tipo (badge) · Valor (+/-) · Concepto · Usuario
4. **Paginación** Anterior / Siguiente

---

## 14. Módulo: Inventario

**Vista:** `inventario/index.php`

### Categorías (ENUM en BD)
| Categoría | Emoji | Regla especial |
|---|---|---|
| Pollo Crudo | 🐔 | Cuartos internos, pollos en UI |
| Papas | 🥔 | — |
| Acompañamientos | 🍌 | — |
| Salsas | 🫙 | — |
| Bebidas | 🥤 | — |
| Otros | 📦 | — |

### Lógica especial Pollo Crudo
- **Form → BD:** cantidad_pollos × 4 = cuartos; costo_pollo ÷ 4 = costo_cuarto
- **BD → Form (editar):** cuartos ÷ 4 = pollos; costo_cuarto × 4 = costo_pollo
- **BD → Tabla:** `intdiv(cuartos, 4) pollos + (cuartos % 4) cuartos`

### Indicadores de stock bajo
- Pollo Crudo: rojo si < 4 cuartos (menos de 1 pollo entero)
- Otros: rojo si ≤ 5 unidades

### Flujos CRUD
```
CREAR: POST /inventario/store → validar → INSERT → flash + redirect
EDITAR: GET /inventario?editar=id → pre-llena form
ACTUALIZAR: POST /inventario/update → UPDATE → flash + redirect
ELIMINAR: Confirm modal JS → POST /inventario/delete
          Si FK violation (ventas) → flash('error') — no se elimina
```

### Buscador
`GET /inventario?q=término` → `LIKE %término%` en campo `articulo`

---

## 15. Módulo: Usuarios

**Vista:** `usuarios/index.php` · **Solo Admin**

### Pantalla
- Tabla cargada por AJAX (`fetch /usuarios/list`) al abrir
- Modal único reutilizado para crear y editar

### Flujo crear
```
POST /usuarios/create (JSON)
  ├─► Valida campos no vacíos
  ├─► Verifica usuario no duplicado (UNIQUE)
  ├─► password_hash(clave, PASSWORD_BCRYPT)
  ├─► INSERT usuarios
  └─► JSON { status: 'ok' | 'error', mensaje }
```

### Flujo editar
- Modal pre-llenado con datos del usuario
- Checkbox "Cambiar contraseña" — oculta/muestra campo clave
- Si no se cambia clave → UPDATE solo nombre, usuario, rol

### Flujo eliminar
- SweetAlert2 confirmación → POST /usuarios/delete → DELETE FROM usuarios

### Badges de rol
- Administrador → fondo ámbar oscuro (#78350f) · texto ámbar (#fde68a)
- Empleado → fondo azul oscuro (#1e3a5f) · texto azul claro (#bfdbfe)

---

## 16. Módulo: Configuración

**Vista:** `config/index.php` · **Solo Admin**

### Sección 1 — Precios de Pollo

| Clave | Descripción |
|---|---|
| `precio_asado_cuarto` | Precio de venta ¼ de pollo asado |
| `precio_asado_medio` | Precio de venta ½ pollo asado |
| `precio_asado_entero` | Precio de venta pollo entero asado |
| `precio_broaster_cuarto` | Precio de venta ¼ de pollo broaster |
| `precio_broaster_medio` | Precio de venta ½ pollo broaster |
| `precio_broaster_entero` | Precio de venta pollo entero broaster |

Si un precio = 0 → el POS usa como fallback el precio del inventario × cuartos.

### Sección 2 — Capacidad de condimentos
- Campo `condimentos_pollos_por_ciclo` — default 1000
- Se guarda junto con los precios en el mismo POST `/config`

### Sección 3 — Estado del ciclo de condimentos
- Barra de progreso de 0% a 100%
- Muestra: N / capacidad pollos · porcentaje
- Estado visual con colores:
  - Verde (< 50%) · Ámbar (50-79%) · Rojo (80-99%) · Rojo intenso (≥ 100%)
- Botón **"🔄 Reiniciar ciclo"** → POST `/config/reset-condimentos`
  - Guarda `condimentos_cuartos_offset = cuartos_vendidos_totales_actuales`
  - El contador vuelve a 0 pollos desde ese momento

---

## 17. Módulo: Reportes Gerenciales

**Vistas:** `reportes/` · **Solo Admin**

### Menú `/reportes`
4 tarjetas de acceso: Diario · Semanal · Mensual · Top Productos

---

### Reporte Diario `/reportes/diario?fecha=YYYY-MM-DD`

**KPIs superiores:**
- Ventas totales (verde)
- Costo productos (inventario)
- Utilidad estimada (ventas − costo)
- Margen %

**Pedidos por tipo:**
- Total pedidos · Local · Para llevar (panel verde)

**Movimientos de caja del día:**
- Ingresos manuales · Gastos operativos · Créditos empleados · ALSÉS

**Ventas por hora** — barras horizontales proporcionales con total y pedidos

**Top 10 productos del día** — tabla con: #, producto, uds, ingresos, costo, margen

**Navegación:** ← Día anterior / Día siguiente →

---

### Reporte Semanal `/reportes/semanal?desde=&hasta=`

**Accesos rápidos:** Esta semana · Esta quincena · Semana pasada

**KPIs:**
- Ventas totales · Utilidad estimada · Margen %
- Total pedidos · Promedio/día · Días con ventas

**Mejor día** (verde) y **Menor día** (rojo) del período

**Barras por día** — una barra proporcional por día con monto

**Top 10 productos del período**

---

### Reporte Mensual `/reportes/mensual?mes=YYYY-MM`

**Navegación:** ← Mes anterior / Mes siguiente →

**KPIs:** Ventas del mes · Costo · Utilidad · Gastos · Créditos · Pedidos

**Resumen financiero detallado:**
```
+ Ventas totales
+ Ingresos manuales
− Gastos operativos
− Créditos empleados
− ALSÉS
= Flujo neto estimado
```

**Barras por día del mes** (escala relativa al día de mayor venta)

**Top 10 productos del mes**

---

### Top Productos `/reportes/productos?desde=&hasta=`

**Accesos rápidos:** Hoy · Este mes · Este año · Todo el tiempo

**Tabla con ranking completo:**
- 🥇🥈🥉 para el podio
- Columnas: #, Producto, Categoría, Pedidos, Uds vendidas (barra + número), Ingresos, Costo, Margen

---

## 18. Seguridad transversal

| Mecanismo | Implementación |
|---|---|
| **CSRF** | Token en sesión, meta tag + campo `_token` en forms, header `X-CSRF-Token` en AJAX |
| **Autenticación** | `AuthMiddleware::handle()` en todos los controladores |
| **Autorización por rol** | `RoleMiddleware::require(Rol::Administrador)` en rutas admin |
| **Contraseñas** | `password_hash()` / `password_verify()` con bcrypt (cost default) |
| **XSS** | `View::escape()` = `htmlspecialchars(ENT_QUOTES\|ENT_SUBSTITUTE)` en toda salida de BD |
| **Inyección SQL** | PDO con prepared statements en todos los modelos |
| **Session fixation** | `Session::regenerate()` tras login exitoso |
| **Stock concurrente** | `SELECT ... FOR UPDATE` dentro de transacción MySQL en `Venta::store()` |
| **Doble pago crédito** | `SELECT ... FOR UPDATE` + verificación de estado en `CreditoEmpleado::pagar()` |
| **Doble apertura** | `UNIQUE KEY uq_apertura_fecha` en `caja_aperturas` |
| **Doble cierre** | `UNIQUE KEY uq_cierre_fecha` en `caja_cierres` |
| **Redirect abierto** | `Response::redirect()` solo acepta rutas internas que empiecen con `/` |
| **Datos sensibles** | `.env` ignorado en git; credenciales de BD fuera del código |

---

## 19. Checklist de verificación

### Autenticación
- [ ] Login correcto redirige al dashboard según rol
- [ ] Login incorrecto muestra toast de error sin revelar cuál campo falló
- [ ] Logout destruye sesión y redirige a `/`
- [ ] Ruta protegida sin sesión redirige a `/`
- [ ] Empleado en `/usuarios`, `/config`, `/reportes`, `/creditos` → redirigido

### Dashboard
- [ ] Admin ve 9 botones
- [ ] Empleado ve 3 botones
- [ ] Total del día aparece bajo VENTAS cuando hay ventas
- [ ] Sin alertas de condimentos cuando % < 50%
- [ ] Alerta ámbar cuando 50% ≤ % < 80%
- [ ] Alerta roja cuando 80% ≤ % < 100%
- [ ] Alerta "AGOTADOS" cuando % ≥ 100%

### Ventas / POS
- [ ] Selector 🏠 Local / 🛵 Para llevar visible y funcional
- [ ] Al elegir "Para llevar": panel verde con nombre, teléfono, dirección aparece
- [ ] Al elegir "Local": panel de cliente desaparece
- [ ] Filtro de categoría "Pollo" muestra solo Pollo Crudo
- [ ] Producto sin stock opacado y no clickeable
- [ ] Al seleccionar Pollo Crudo: aparece selector Preparación y Corte
- [ ] No se puede elegir corte sin haber elegido preparación
- [ ] Subtotal correcto según precio configurado o precio inventario
- [ ] Alerta de stock insuficiente si cantidad × mult > disponible
- [ ] Acompañamientos se ofrecen tras agregar pollo
- [ ] Ítem puede eliminarse individualmente del carrito
- [ ] "Registrar Pedido" descuenta stock en BD y actualiza tarjetas UI
- [ ] `tipo_pedido` + datos de cliente se guardan en BD en cada línea de venta
- [ ] "Enviar a Caja" marca ventas como liquidadas y registra ingreso en historial_caja
- [ ] Factura del día muestra tipo de pedido (🏠/🛵) por cada pedido
- [ ] Imprimir factura abre diálogo de impresión

### Caja
- [ ] Saldo actual correcto desde `caja.total`
- [ ] Admin: banner verde si existe apertura del día
- [ ] Admin: banner ámbar si no hay apertura
- [ ] Admin: sección ALSÉ visible con borde ámbar diferenciado
- [ ] Añadir dinero: saldo sube + registro en historial_caja
- [ ] Retirar dinero: saldo baja + registro en historial_caja (sin quedar negativo)
- [ ] ALSÉ: saldo baja + registro en `retiros_seguridad` (no en historial_caja)
- [ ] Banner dorado de ventas sin liquidar aparece cuando hay pendiente
- [ ] Tabla de actividad unifica movimientos de caja + ventas del día

### Apertura de Caja
- [ ] Admin puede registrar apertura una sola vez por día
- [ ] Subtotales se calculan en tiempo real por denominación
- [ ] Total de la base se actualiza automáticamente
- [ ] Tras registrar: muestra resumen con base y desglose de denominaciones
- [ ] Si ya existe apertura: muestra resumen, no muestra formulario
- [ ] Segundo intento de apertura el mismo día → error

### Cierre de Caja
- [ ] Sin apertura del día → redirige a /caja/apertura con mensaje
- [ ] Muestra base inicial de la apertura del día
- [ ] Valores del día precalculados automáticamente (ventas, ingresos, gastos, créditos, ALSÉS)
- [ ] Cálculo en tiempo real: dinero esperado al cambiar movimientos
- [ ] Arqueo físico: subtotales por denominación + total contado en tiempo real
- [ ] Resultado: diferencia en tiempo real (sobrante verde / faltante rojo)
- [ ] Fórmula correcta: base + ventas + entradas − gastos − créditos − alsés − otras
- [ ] Un solo cierre por día
- [ ] Si ya existe cierre: muestra resumen, no muestra formulario

### Créditos a Empleados
- [ ] Solo aparece botón "Nuevo Crédito" si hay empleados en BD
- [ ] KPIs: pendientes, vencidos, cartera total correctos
- [ ] Crear crédito: sale de caja + registro en historial_caja
- [ ] No puede crear crédito si saldo de caja es insuficiente
- [ ] Pagar crédito: entra a caja + registro en historial_caja
- [ ] Sin doble pago (FOR UPDATE + verificación de estado)
- [ ] Vencer manual: cambia estado a 'vencido'
- [ ] Al cargar la vista: vencidos automáticos por fecha

### Historial
- [ ] Sin filtro: todos los registros paginados (50/página)
- [ ] Filtro por fechas acota correctamente
- [ ] Resumen muestra ingresos, retiros y neto del período
- [ ] Paginación Anterior/Siguiente funciona
- [ ] Liquidaciones aparecen como ingreso con concepto "Liquidación: N venta(s)"
- [ ] Créditos aparecen como retiro con concepto "Crédito empleado: nombre"
- [ ] Pagos de crédito aparecen como ingreso con concepto "Pago crédito: nombre"

### Inventario
- [ ] Artículo se crea y aparece en tabla
- [ ] Editar: pre-llena formulario con datos actuales (conversión Pollo Crudo aplicada)
- [ ] Pollo Crudo: cantidad ingresada × 4 = cuartos en BD
- [ ] Pollo Crudo: cuartos ÷ 4 = pollos al editar
- [ ] Stock bajo en rojo (< 4 cuartos para pollo; ≤ 5 uds para otros)
- [ ] Buscador filtra por nombre parcial
- [ ] Eliminar pide confirmación antes de borrar
- [ ] Artículo con ventas asociadas no puede eliminarse (FK RESTRICT → mensaje claro)

### Usuarios
- [ ] Tabla carga por AJAX al abrir
- [ ] Crear usuario con campos completos
- [ ] Usuario duplicado → error sin crear registro
- [ ] Editar: campo contraseña oculto por defecto, visible con checkbox
- [ ] Eliminar → confirmación SweetAlert2
- [ ] Badges de rol correctos (ámbar = admin, azul = empleado)

### Configuración
- [ ] Los 6 precios se guardan y persisten
- [ ] Precio 0 → POS usa fallback de inventario
- [ ] Precio > 0 → POS usa precio configurado para el corte
- [ ] Capacidad de condimentos se guarda junto con los precios
- [ ] Barra de progreso muestra % correcto
- [ ] Botón "Reiniciar ciclo" → contador vuelve a 0 pollos
- [ ] Estado del ciclo se actualiza inmediatamente tras reiniciar

### Reportes Gerenciales
- [ ] Menú de reportes accesible solo para admin
- [ ] Diario: KPIs correctos, barras por hora, top productos, navegación de días
- [ ] Diario: distingue pedidos local vs para llevar
- [ ] Semanal: rango de fechas libre, accesos rápidos (semana/quincena)
- [ ] Semanal: mejor y menor día resaltados
- [ ] Mensual: navegación entre meses con `<input type="month">`
- [ ] Mensual: resumen financiero con todas las líneas
- [ ] Top Productos: ranking con podio 🥇🥈🥉, barras proporcionales
- [ ] Top Productos: filtrable por cualquier rango de fechas

### Seguridad
- [ ] Todo POST tiene campo `_token` oculto
- [ ] Todo AJAX incluye header `X-CSRF-Token`
- [ ] Token inválido → 403
- [ ] `<script>alert(1)</script>` en campos de texto → escapa correctamente
- [ ] Acceso a ruta admin como empleado → redirige
- [ ] URL con `../../../etc` → no expone archivos del servidor

---

*Sistema Kokoro Pollo v2.0 · Documentado el 2026-06-01*  
*Sprint 1: Apertura, Cierre, Créditos, ALSÉS · Sprint 2: Para Llevar, Condimentos · Sprint 3: Reportes*

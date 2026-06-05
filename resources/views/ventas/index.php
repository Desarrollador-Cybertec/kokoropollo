<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

$productos            = (isset($productos) && is_array($productos)) ? $productos : [];
$productosJson        = isset($productosJson) ? (string) $productosJson : '[]';
$categoriasConfig     = (isset($categoriasConfig) && is_array($categoriasConfig)) ? $categoriasConfig : [];
$totalDia             = isset($totalDia) ? (float) $totalDia : 0.0;
$dashboardUrl         = isset($dashboardUrl) ? (string) $dashboardUrl : '/dashboard';
$preciosPolloJson     = isset($preciosPolloJson) ? (string) $preciosPolloJson : '{"cuarto":0,"medio":0,"entero":0}';
$pendienteLiquidacion = isset($pendienteLiquidacion) ? (float) $pendienteLiquidacion : 0.0;
$cajaTotal            = isset($cajaTotal) ? (float) $cajaTotal : 0.0;
$cajaMovimientos      = (isset($cajaMovimientos) && is_array($cajaMovimientos)) ? $cajaMovimientos : [];
$cajaIngresos         = isset($cajaIngresos) ? (float) $cajaIngresos : 0.0;
$cajaRetiros          = isset($cajaRetiros) ? (float) $cajaRetiros : 0.0;
$esAdmin              = isset($esAdmin) ? (bool) $esAdmin : false;
$puedeAjustarCaja     = isset($puedeAjustarCaja) ? (bool) $puedeAjustarCaja : false;
$empleadosCredito     = (isset($empleadosCredito) && is_array($empleadosCredito)) ? $empleadosCredito : [];

$pageTitle = 'Ventas — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<style>
:root {
    --rojo-alt:  #4a0e0e;
    --rojo-bord: #5a1a1a;
    --oro-cl:    #e6c857;
}
body { background: linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%); min-height: 100vh; }

/* ── Tarjetas de producto ── */
.prod-card {
    background-color: var(--rojo-card);
    border: 2px solid var(--rojo-bord);
    border-radius: .875rem;
    padding: 1rem;
    cursor: pointer;
    transition: all .18s;
    text-align: center;
    position: relative;
}
.prod-card:hover  { border-color: var(--oro); background-color: var(--rojo-alt); }
.prod-card.activa { border-color: var(--oro); background-color: var(--rojo-alt);
                    box-shadow: 0 0 0 3px var(--oro); }
.prod-card .cat-emoji { font-size: 2rem; }
.prod-card .prod-nom  { font-weight: 800; font-size: 1rem; color: white; margin: .3rem 0 .15rem; line-height: 1.2; }
.prod-card .prod-prec { font-size: .9rem; color: var(--oro); font-weight: 700; }
.prod-card .prod-stk  { font-size: .75rem; margin-top:.2rem; }
.prod-card.sin-stock  { opacity: .4; cursor: not-allowed; }

/* ── Panel configurador ── */
.config-panel {
    background-color: var(--rojo-card);
    border: 2px solid var(--oro);
    border-radius: 1rem;
    padding: 1.25rem;
}

/* ── Botones de corte ── */
.corte-btn {
    display: flex; flex-direction: column; align-items: center; gap: .3rem;
    padding: .9rem .5rem;
    border: 2px solid var(--rojo-bord);
    border-radius: .75rem;
    background-color: var(--rojo-deep);
    color: #ccc;
    cursor: pointer; transition: all .18s; text-align: center;
    font-size: .85rem; font-weight: 700;
}
.corte-btn:hover  { border-color: var(--oro); background-color: var(--rojo-alt); color: var(--oro); }
.corte-btn.activo { border-color: var(--oro); background-color: var(--rojo-alt);
                    color: var(--oro); box-shadow: 0 0 0 3px var(--oro); }
.corte-btn .ce    { font-size: 1.6rem; }
.corte-btn .cs    { font-size: .7rem; opacity: .65; line-height: 1.2; }

/* ── Input cantidad ── */
.q-btn {
    background-color: var(--rojo-bord); color: white; border: none;
    border-radius: .5rem; width: 2.5rem; height: 2.5rem;
    font-size: 1.3rem; font-weight: 900; cursor: pointer; transition: background .15s;
}
.q-btn:hover { background-color: var(--oro); color: var(--rojo-dark); }
.q-inp {
    width: 4rem; text-align: center;
    background-color: var(--rojo-deep); color: white;
    border: 2px solid var(--rojo-bord); border-radius: .5rem;
    padding: .4rem; font-size: 1.3rem; font-weight: 900; outline: none;
}

/* ── Carrito ── */
.carrito-item {
    display: flex; align-items: center; gap: .75rem;
    padding: .75rem 1rem;
    background-color: var(--rojo-deep);
    border-radius: .75rem;
    border: 1px solid var(--rojo-bord);
}
.carrito-item:not(:last-child) { margin-bottom: .5rem; }

/* ── Selector tipo pedido ── */
.tipo-btn {
    padding: .45rem 1.1rem; border-radius: 9999px; font-weight: 700; font-size: .9rem;
    border: 2px solid var(--rojo-bord); background-color: var(--rojo-deep); color: #ccc;
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.tipo-btn:hover  { border-color: var(--oro); color: var(--oro); }
.tipo-btn.activa { border-color: var(--oro); background-color: var(--rojo-alt); color: var(--oro);
                   box-shadow: 0 0 0 2px var(--oro); }
.tipo-btn.llevar-activa { border-color: #34d399; background-color: #064e3b; color: #34d399;
                          box-shadow: 0 0 0 2px #34d399; }

/* ── Filtros categoría ── */
.cat-btn {
    padding: .5rem 1rem; border-radius: 9999px; font-weight: 700; font-size: .95rem;
    border: 2px solid var(--rojo-bord); background-color: var(--rojo-deep); color: #ccc;
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.cat-btn:hover  { border-color: var(--oro); color: var(--oro); }
.cat-btn.activa { border-color: var(--oro); background-color: var(--rojo-alt); color: var(--oro); }

/* ── Botón vaciar ── */
.btn-vaciar {
    background-color: var(--rojo-bord); color: #fca5a5;
    transition: background-color .15s; cursor: pointer;
}
.btn-vaciar:hover { background-color: #7f1d1d; }

/* ── Panel caja (derecha) ── */
.caja-input {
    background-color: var(--rojo-deep);
    color: white;
    border: 1.5px solid var(--rojo-bord);
    border-radius: .5rem;
    padding: .5rem .75rem;
    font-size: .95rem;
    width: 100%;
    outline: none;
    transition: border-color .15s;
}
.caja-input:focus { border-color: var(--oro); }
</style>

<body class="py-4 pb-16">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<!-- ══ LAYOUT DIVIDIDO ══════════════════════════════════════════ -->
<div class="flex flex-col lg:flex-row gap-4 px-3 max-w-[1700px] mx-auto" style="min-height:calc(100vh - 2rem);">

    <!-- ══ COLUMNA DERECHA — Ventas ════════════════════════ -->
    <div class="lg:w-3/5 space-y-4 lg:order-2">

        <!-- Encabezado -->
        <div class="flex items-center justify-between">
            <a href="<?= View::escape($dashboardUrl) ?>"
               class="font-black text-sm px-4 py-2 rounded-xl btn-primary">
                ← Regresar
            </a>
            <h1 class="font-black text-2xl tracking-wide" style="color:var(--oro);">🛒 Ventas</h1>
            <div class="w-24"></div>
        </div>

        <!-- ══ SELECTOR TIPO PEDIDO ══════════════════════════ -->
        <div class="rounded-2xl px-4 py-3 shadow-xl flex flex-wrap items-center gap-3"
             style="background-color:var(--rojo-card);">
            <span class="text-sm font-black uppercase tracking-wider" style="color:#9ca3af;">Tipo:</span>
            <button class="tipo-btn activa" data-tipo="local" onclick="seleccionarTipo(this)">
                🏠 Local
            </button>
            <button class="tipo-btn" data-tipo="llevar" onclick="seleccionarTipo(this)">
                🛵 Para llevar
            </button>
            <span id="badgeTipo" class="hidden text-xs font-bold px-3 py-1 rounded-full"
                  style="background-color:#064e3b; color:#34d399;">Para llevar</span>
        </div>

        <!-- Panel datos cliente (solo Para llevar) -->
        <div id="panelCliente" class="hidden rounded-2xl p-4 shadow-xl space-y-3"
             style="background-color:#064e3b; border:2px solid #34d399;">
            <p class="text-sm font-black uppercase tracking-wider" style="color:#34d399;">
                🛵 Datos del cliente (opcionales)
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" id="clienteNombre" placeholder="Nombre del cliente"
                       maxlength="100"
                       class="text-base px-3 py-2 rounded-xl font-semibold text-white"
                       style="background-color:rgba(0,0,0,.3); border:1px solid #34d399; outline:none;">
                <input type="tel" id="clienteTelefono" placeholder="Teléfono"
                       maxlength="20"
                       class="text-base px-3 py-2 rounded-xl font-semibold text-white"
                       style="background-color:rgba(0,0,0,.3); border:1px solid #34d399; outline:none;">
                <input type="text" id="clienteDireccion" placeholder="Dirección de entrega"
                       maxlength="255"
                       class="text-base px-3 py-2 rounded-xl font-semibold text-white"
                       style="background-color:rgba(0,0,0,.3); border:1px solid #34d399; outline:none;">
            </div>
        </div>

        <!-- ══ BLOQUE 1 — Seleccionar producto ══════════════ -->
        <div class="rounded-2xl p-4 shadow-xl" style="background-color:var(--rojo-card);">
            <h2 class="font-black text-lg mb-3" style="color:var(--oro);">1️⃣ Seleccionar producto</h2>

            <div class="flex flex-wrap gap-2 mb-3" id="catFiltros">
                <button class="cat-btn activa" data-cat="Todos" onclick="filtrarCategoria(this)">
                    🔎 Todos
                </button>
                <?php foreach ($categoriasConfig as $cat => $cfg): ?>
                    <button class="cat-btn" data-cat="<?= $cat ?>" onclick="filtrarCategoria(this)">
                        <?= $cfg['emoji'] ?> <?= $cfg['label'] ?? $cat ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div id="gridProductos" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <?php foreach ($productos as $p): ?>
                    <?php
                    $esVirtual  = ($p['id'] < 0 || ($p['es_virtual'] ?? false));
                    $sinStock   = !$esVirtual && (int)$p['cantidad'] <= 0;
                    ?>
                    <div class="prod-card <?= $sinStock ? 'sin-stock' : '' ?>"
                         data-id="<?= (int)$p['id'] ?>"
                         data-nombre="<?= View::escape($p['articulo']) ?>"
                         data-precio="<?= (float)$p['valor'] ?>"
                         data-stock="<?= $esVirtual ? 9999 : (int)$p['cantidad'] ?>"
                         data-cat="<?= View::escape($p['categoria']) ?>"
                         onclick="seleccionarProducto(this)">
                        <div class="cat-emoji">
                            <?= $categoriasConfig[$p['categoria']]['emoji'] ?? '📦' ?>
                        </div>
                        <div class="prod-nom"><?= View::escape($p['articulo']) ?></div>
                        <?php if ($esVirtual): ?>
                            <div class="prod-prec">$<?= number_format((float)$p['valor'], 0, ',', '.') ?></div>
                            <div class="prod-stk text-green-400">Disponible</div>
                        <?php elseif ($p['categoria'] === 'Pollo Crudo'): ?>
                            <div class="prod-prec">Costo: $<?= number_format((float)$p['valor'] * 4, 0, ',', '.') ?>/pollo</div>
                            <div class="prod-stk <?= (int)$p['cantidad'] <= 4 ? 'text-red-400' : 'text-green-400' ?>">
                                <?= intdiv((int)$p['cantidad'], 4) ?> pollos (<?= (int)$p['cantidad'] ?> cuartos)
                            </div>
                        <?php else: ?>
                            <div class="prod-prec">$<?= number_format((float)$p['valor'], 0, ',', '.') ?></div>
                            <div class="prod-stk <?= (int)$p['cantidad'] <= 5 ? 'text-red-400' : 'text-green-400' ?>">
                                <?= (int)$p['cantidad'] ?> disponibles
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($productos)): ?>
                    <div class="col-span-3 text-center py-8" style="color:#9ca3af;">
                        Sin productos con stock disponible
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ══ BLOQUE 2 — Configurar ════════════════════════ -->
        <div id="configPanel" class="config-panel hidden">
            <div class="flex justify-between items-start mb-4 flex-wrap gap-3">
                <div>
                    <p class="text-sm font-semibold" style="color:#9ca3af;">Producto seleccionado</p>
                    <p id="cfgNombre" class="text-2xl font-black" style="color:var(--oro);"></p>
                    <p id="cfgPrecio" class="text-base font-semibold text-gray-300"></p>
                </div>
                <button onclick="deseleccionarProducto()"
                        class="text-sm px-4 py-2 rounded-xl font-bold text-gray-400 border border-gray-600 hover:border-red-500 hover:text-red-400 transition-all">
                    ✕ Cambiar
                </button>
            </div>

            <div id="seccionCorte" class="hidden mb-5">
                <p class="text-sm font-bold mb-3" style="color:#9ca3af;">¿Cuánto lleva?</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <button type="button" class="corte-btn" data-mult="1" data-corte-key="cuarto" onclick="seleccionarCorte(this,'1/4 Pierna-Pernil')">
                        <span class="ce">🍗</span><span>¼ Pierna</span><span class="cs">Pierna + Pernil</span><span class="cs">× 1 ud</span>
                    </button>
                    <button type="button" class="corte-btn" data-mult="1" data-corte-key="cuarto" onclick="seleccionarCorte(this,'1/4 Pechuga-Ala')">
                        <span class="ce">🍗</span><span>¼ Pechuga</span><span class="cs">Pechuga + Ala</span><span class="cs">× 1 ud</span>
                    </button>
                    <button type="button" class="corte-btn" data-mult="2" data-corte-key="medio" onclick="seleccionarCorte(this,'Medio Pollo')">
                        <span class="ce">🍗🍗</span><span>Medio Pollo</span><span class="cs">2 cuartos</span><span class="cs">× 2 uds</span>
                    </button>
                    <button type="button" class="corte-btn" data-mult="4" data-corte-key="entero" onclick="seleccionarCorte(this,'Pollo Entero')">
                        <span class="ce">🐔</span><span>Pollo Entero</span><span class="cs">4 cuartos</span><span class="cs">× 4 uds</span>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-5 mt-2">
                <div>
                    <p class="text-sm font-bold mb-2" style="color:#9ca3af;">Cantidad</p>
                    <div class="flex items-center gap-2">
                        <button class="q-btn" onclick="cambiarCantidad(-1)">−</button>
                        <input type="number" id="cfgCantidad" value="1" min="1"
                               class="q-inp" oninput="recalcSubtotal()">
                        <button class="q-btn" onclick="cambiarCantidad(1)">+</button>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-bold mb-2" style="color:#9ca3af;">Subtotal</p>
                    <p id="cfgSubtotal" class="text-3xl font-black" style="color:var(--oro);">$0</p>
                </div>
                <div class="flex-1 flex justify-end">
                    <button id="btnAgregar" onclick="agregarAlCarrito()"
                            class="font-black text-xl px-8 py-4 rounded-2xl shadow-lg btn-green" disabled>
                        ➕ Agregar al pedido
                    </button>
                </div>
            </div>
            <div id="alertaStockCfg"
                 class="hidden mt-3 text-center font-bold px-4 py-3 rounded-xl text-base"
                 style="background-color:#7f1d1d; color:#fca5a5; border:1px solid #ef4444;">
                ⚠️ Stock insuficiente para esta cantidad
            </div>
        </div>

        <!-- ══ BLOQUE 2.5 — Acompañamientos ═════════════════ -->
        <div id="seccionAcomp" class="hidden config-panel" style="border-color:#d4af37;">
            <div class="flex justify-between items-center mb-3">
                <div>
                    <h2 class="font-black text-xl" style="color:var(--oro);">🍽️ ¿Qué lleva de acompañamiento?</h2>
                    <p class="text-sm mt-1" style="color:#9ca3af;">Toca lo que quiere agregar — luego haz clic en Listo</p>
                </div>
                <button onclick="cerrarAcomp()"
                        class="text-sm px-4 py-2 rounded-xl font-bold text-gray-400 border border-gray-600 hover:border-red-500 hover:text-red-400 transition-all">
                    ✕ Saltar
                </button>
            </div>
            <div id="gridAcomp" class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4"></div>
            <button onclick="agregarAcompSeleccionados()"
                    class="w-full font-black text-xl py-4 rounded-2xl btn-green">
                ✅ Listo — ver pedido
            </button>
        </div>

        <!-- ══ BLOQUE 3 — Carrito ════════════════════════════ -->
        <div id="seccionCarrito" class="hidden rounded-2xl p-4 shadow-xl" style="background-color:var(--rojo-card);">
            <h2 class="font-black text-xl mb-4" style="color:var(--oro);">
                🧺 Pedido actual
                <span id="cantItems" class="text-base font-semibold text-gray-400"></span>
            </h2>
            <div id="listaCarrito" class="space-y-2 mb-5"></div>

            <div class="flex justify-between items-center rounded-xl px-5 py-4 mb-4"
                 style="background-color:var(--rojo-deep); border:2px solid var(--rojo-bord);">
                <span class="font-bold text-lg text-gray-300">TOTAL DEL PEDIDO</span>
                <span id="totalPedido" class="font-black text-4xl" style="color:var(--oro);">$0</span>
            </div>

            <div class="grid sm:grid-cols-3 gap-2 mb-4">
                <div class="rounded-xl px-4 py-3" style="background-color:#13331f; border:1px solid #1d6b3a;">
                    <p class="text-xs font-bold uppercase" style="color:#9ad9b0;">Venta</p>
                    <p id="resumenVenta" class="text-xl font-black" style="color:#9ad9b0;">$0</p>
                </div>
                <div class="rounded-xl px-4 py-3" style="background-color:#3a2a0f; border:1px solid #8a6b25;">
                    <p class="text-xs font-bold uppercase" style="color:#f7d58e;">Costo</p>
                    <p id="resumenCosto" class="text-xl font-black" style="color:#f7d58e;">$0</p>
                </div>
                <div class="rounded-xl px-4 py-3" style="background-color:#142f4a; border:1px solid #2563eb;">
                    <p class="text-xs font-bold uppercase" style="color:#93c5fd;">Margen</p>
                    <p id="resumenMargen" class="text-xl font-black" style="color:#93c5fd;">$0</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button id="btnRegistrar" onclick="registrarPedido()"
                        class="flex-1 font-black text-xl py-5 rounded-2xl shadow-xl uppercase tracking-wide btn-green"
                        style="min-width:200px;">
                    ✅ Registrar Pedido
                </button>
                <button onclick="vaciarCarrito()"
                        class="font-bold text-lg px-6 py-5 rounded-2xl btn-vaciar">
                    🗑️ Vaciar
                </button>
            </div>
            <div id="alertaRegistro" class="hidden mt-3 text-center font-bold px-4 py-3 rounded-xl text-base"
                 style="background-color:#7f1d1d; color:#fca5a5; border:1px solid #ef4444;"></div>
        </div>

        <!-- ══ BLOQUE 4 — Historial del día ══════════════════ -->
        <div id="seccionHistorial" class="hidden space-y-3">
            <div class="flex justify-between items-center">
                <h2 class="font-black text-xl" style="color:var(--oro);">📊 Pedidos de hoy</h2>
                <button id="btnFactura" onclick="generarFactura()"
                        class="font-black text-sm px-4 py-2 rounded-xl btn-blue">
                    🧾 Factura
                </button>
            </div>
            <div id="listaPedidos" class="space-y-3"></div>
            <div class="px-5 py-4 rounded-xl text-right font-black text-xl"
                 style="background-color:var(--rojo-alt); color:var(--oro); border:2px solid var(--rojo-bord);">
                Total del día: $<span id="totalDiaSpan"><?= number_format((float)$totalDia, 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- ══ BLOQUE 5 — Liquidar a caja ═══════════════════ -->
        <div class="rounded-2xl p-5 shadow-xl" style="background-color:var(--rojo-card); border:2px solid var(--oro);">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="font-black text-lg mb-1" style="color:var(--oro);">💰 Por liquidar a caja</h3>
                    <p class="font-black text-4xl text-white">
                        $<span id="pendienteSpan"><?= number_format($pendienteLiquidacion, 0, ',', '.') ?></span>
                    </p>
                    <p id="pendienteDesc" class="text-sm mt-1" style="color:#9ca3af;">
                        <?= $pendienteLiquidacion > 0 ? 'Ventas pendientes de enviar a caja' : 'Todo está liquidado ✓' ?>
                    </p>
                </div>
                <button id="btnLiquidar" onclick="liquidarVentas()"
                        class="font-black text-xl px-8 py-4 rounded-2xl shadow-lg btn-primary"
                        <?= $pendienteLiquidacion <= 0 ? 'disabled style="opacity:.45;cursor:not-allowed;"' : '' ?>>
                    🏦 Enviar a Caja
                </button>
            </div>
            <div id="alertaLiquidacion" class="hidden mt-3 text-center font-bold px-4 py-3 rounded-xl text-base"></div>
        </div>

    </div><!-- /columna ventas -->

    <!-- ══ COLUMNA IZQUIERDA — Caja ═══════════════════════════ -->
    <div class="lg:w-2/5 space-y-4 lg:order-1">

        <h2 class="font-black text-2xl text-center tracking-wide" style="color:var(--oro);">💰 Caja</h2>

        <!-- Total en caja -->
        <div class="bg-white text-5xl font-black text-center py-6 rounded-2xl shadow-2xl tracking-wider"
             style="color:var(--rojo-dark);">
            $<span id="cajaTotalDisplay"><?= number_format($cajaTotal, 0, ',', '.') ?></span>
        </div>

        <!-- Mini-resumen -->
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl px-4 py-3 text-center" style="background-color:#134e2a;">
                <div class="text-xs font-bold uppercase tracking-wider text-green-300 mb-1">Ingresos hoy</div>
                <div class="text-xl font-black text-green-300" id="cajaIngresosDisplay">
                    +$<?= number_format($cajaIngresos, 0, ',', '.') ?>
                </div>
            </div>
            <div class="rounded-xl px-4 py-3 text-center" style="background-color:#4a0e0e;">
                <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#fca5a5;">Retiros hoy</div>
                <div class="text-xl font-black" style="color:#fca5a5;" id="cajaRetirosDisplay">
                    -$<?= number_format($cajaRetiros, 0, ',', '.') ?>
                </div>
            </div>
        </div>

        <!-- Formularios Añadir / Retirar (compacto, solo Admin/Jefe) -->
        <?php if ($puedeAjustarCaja): ?>
        <div class="rounded-2xl p-3 shadow-xl" style="background-color:var(--rojo-card);">
            <h3 class="font-black text-xs uppercase tracking-wider mb-2" style="color:var(--oro);">⚖️ Ajustar caja</h3>
            <div class="grid grid-cols-2 gap-2">
                <form id="formAnadir" onsubmit="submitAjusteCaja(event, 'anadir')" class="space-y-1">
                    <div class="flex gap-1">
                        <input type="number" step="1" min="1" placeholder="Valor" required
                               name="valor" class="caja-input text-sm" style="flex:1; padding:.35rem .5rem;">
                        <input type="text" placeholder="Concepto" maxlength="255"
                               name="concepto" class="caja-input text-sm" style="flex:2; padding:.35rem .5rem;">
                    </div>
                    <button type="submit"
                            class="w-full font-black text-sm py-2 rounded-lg uppercase tracking-wide btn-green">
                        ➕ Añadir
                    </button>
                </form>
                <form id="formRetirar" onsubmit="submitAjusteCaja(event, 'retirar')" class="space-y-1">
                    <div class="flex gap-1">
                        <input type="number" step="1" min="1" placeholder="Valor" required
                               name="valor" class="caja-input text-sm" style="flex:1; padding:.35rem .5rem;">
                        <input type="text" placeholder="Concepto" maxlength="255"
                               name="concepto" class="caja-input text-sm" style="flex:2; padding:.35rem .5rem;">
                    </div>
                    <button type="submit"
                            class="w-full font-black text-sm py-2 rounded-lg uppercase tracking-wide btn-danger">
                        ➖ Retirar
                    </button>
                </form>
            </div>
            <div id="alertaCaja" class="hidden text-center font-bold px-2 py-1.5 rounded-lg text-xs mt-2"></div>
        </div>
        <?php endif; /* puedeAjustarCaja */ ?>

        <?php if ($esAdmin): ?>
        <!-- ══ PANEL PRÉSTAMOS A EMPLEADOS ══════════════════════ -->
        <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color:var(--rojo-card);">
            <div class="px-4 py-3 flex justify-between items-center" style="background-color:var(--rojo-mid);">
                <div class="flex items-center gap-3">
                    <h3 class="font-black text-sm uppercase tracking-wider" style="color:#fbbf24;">
                        💳 Préstamos empleados
                    </h3>
                    <span id="badgeCreditos" class="text-xs font-black px-2 py-0.5 rounded-full hidden"
                          style="background-color:#7f1d1d; color:#fca5a5; border:1px solid #ef4444;"></span>
                </div>
                <button onclick="toggleFormCredito()"
                        id="btnToggleCredito"
                        class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all"
                        style="background-color:#78350f; color:#fbbf24; border:1px solid #f59e0b;">
                    ＋ Nuevo
                </button>
            </div>

            <!-- Formulario nuevo préstamo (oculto por defecto) -->
            <div id="formCreditoWrap" class="hidden px-4 py-3 border-b" style="border-color:var(--rojo-bord); background-color:rgba(0,0,0,.2);">
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <div class="col-span-2">
                        <label class="text-xs font-bold mb-1 block" style="color:#9ca3af;">Empleado</label>
                        <select id="creditoEmpleado" class="caja-input text-sm w-full">
                            <option value="">— Seleccionar —</option>
                            <?php if (empty($empleadosCredito)): ?>
                            <option value="" disabled>Sin empleados registrados</option>
                        <?php else: ?>
                            <?php foreach ($empleadosCredito as $emp): ?>
                            <option value="<?= (int)$emp['id'] ?>"><?= View::escape($emp['nombre']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold mb-1 block" style="color:#9ca3af;">Valor ($)</label>
                        <input type="number" id="creditoValor" min="1" step="1" placeholder="0"
                               class="caja-input text-sm w-full">
                    </div>
                    <div>
                        <label class="text-xs font-bold mb-1 block" style="color:#9ca3af;">Fecha compromiso</label>
                        <input type="date" id="creditoFecha" class="caja-input text-sm w-full"
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-span-2">
                        <label class="text-xs font-bold mb-1 block" style="color:#9ca3af;">Observaciones (opcional)</label>
                        <input type="text" id="creditoObs" maxlength="200" placeholder="Motivo del préstamo..."
                               class="caja-input text-sm w-full">
                    </div>
                </div>
                <button onclick="crearCredito()"
                        id="btnCrearCredito"
                        class="w-full font-black text-sm py-2 rounded-lg btn-primary">
                    💳 Registrar préstamo
                </button>
                <div id="alertaCredito" class="hidden mt-2 text-center font-bold px-2 py-1.5 rounded-lg text-xs"></div>
            </div>

            <!-- Lista de créditos pendientes/vencidos -->
            <div id="listaCreditos" class="text-sm" style="max-height:260px; overflow-y:auto;">
                <div class="px-4 py-3 text-center text-xs" style="color:#6b7280;">Cargando...</div>
            </div>

            <div class="px-4 py-2 text-xs flex justify-between items-center" style="color:#6b7280; border-top:1px solid var(--rojo-bord);">
                <span id="resumenCreditos">—</span>
                <a href="/creditos" class="font-bold hover:text-yellow-400 transition-colors">Ver todo →</a>
            </div>
        </div>
        <?php endif; ?>


        <!-- Actividad de hoy -->
        <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color:var(--rojo-card);">
            <div class="px-4 py-3 flex justify-between items-center" style="background-color:var(--rojo-mid);">
                <h3 class="font-black text-sm uppercase tracking-wider" style="color:var(--oro);">
                    🕐 Actividad de hoy
                </h3>
                <button onclick="refrescarCaja()" title="Actualizar"
                        class="text-xs font-bold px-2 py-1 rounded-lg text-gray-400 border border-gray-600 hover:border-yellow-500 hover:text-yellow-400 transition-all">
                    ↻
                </button>
            </div>
            <div id="cajaMov" style="max-height:320px; overflow-y:auto;">
                <div id="cajaMovList" class="text-sm"></div>
            </div>
        </div>

        <!-- Accesos rápidos de caja -->
        <div class="text-center pb-4 flex flex-wrap gap-2 justify-center">
            <a href="/caja" class="font-bold text-sm px-5 py-2 rounded-xl btn-secondary inline-block">
                📋 Gestión completa de caja →
            </a>
            <?php if ($esAdmin): ?>
            <a href="/caja#seccionCierre"
               class="font-bold text-sm px-5 py-2 rounded-xl inline-block"
               style="background-color:#7f1d1d; color:#fca5a5; border:1px solid #ef4444;">
                🔒 Cierre de caja
            </a>
            <?php endif; ?>
        </div>

    </div><!-- /columna derecha -->

</div><!-- /layout dividido -->

<!-- Modal Factura -->
<div id="modalFactura"
     class="hidden fixed inset-0 flex items-center justify-center z-50 p-4"
     style="background-color:rgba(0,0,0,.75);">
    <div class="w-full max-w-2xl rounded-2xl shadow-2xl p-8 max-h-[90vh] overflow-y-auto"
         style="background-color:var(--rojo-card);">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-black" style="color:var(--oro);">🧾 Factura del día</h2>
            <button onclick="document.getElementById('modalFactura').classList.add('hidden')"
                    class="font-black text-4xl leading-none text-red-400 hover:text-red-300">&times;</button>
        </div>
        <div id="contenidoFactura" class="text-white text-sm"></div>
        <button onclick="imprimirFactura()"
                class="mt-6 w-full font-black text-xl py-4 rounded-xl btn-primary">
            🖨️ Imprimir
        </button>
    </div>
</div>

<script>
const PRODUCTOS     = <?= $productosJson ?>;
const PRECIOS_POLLO = <?= $preciosPolloJson ?>;
let totalDiaAcum    = <?= (float)$totalDia ?>;
let pendienteAcum   = <?= (float)$pendienteLiquidacion ?>;
const CAJA_MOV_INICIAL = <?= json_encode($cajaMovimientos, JSON_UNESCAPED_UNICODE) ?>;
const ES_ADMIN      = <?= $esAdmin ? 'true' : 'false' ?>;
</script>
<script src="/js/ventas.js"></script>
<?php if ($esAdmin): ?>
<script>
/* ── Panel de préstamos a empleados ── */
function toggleFormCredito() {
    const wrap = document.getElementById('formCreditoWrap');
    const btn  = document.getElementById('btnToggleCredito');
    const open = wrap.classList.toggle('hidden');
    btn.textContent = open ? '＋ Nuevo' : '✕ Cerrar';
}

async function cargarCreditos() {
    try {
        const res  = await fetch('/creditos/list', { headers: { 'X-CSRF-Token': CSRF } });
        const data = await res.json();
        renderCreditos(data);
    } catch {
        document.getElementById('listaCreditos').innerHTML =
            '<div class="px-4 py-3 text-center text-xs" style="color:#ef4444;">Error al cargar</div>';
    }
}

function renderCreditos(lista) {
    const el = document.getElementById('listaCreditos');
    const badge = document.getElementById('badgeCreditos');
    const resumen = document.getElementById('resumenCreditos');

    if (!lista.length) {
        el.innerHTML = '<div class="px-4 py-3 text-center text-xs" style="color:#6b7280;">Sin préstamos pendientes ✓</div>';
        badge.classList.add('hidden');
        resumen.textContent = 'Sin deuda pendiente';
        return;
    }

    const total = lista.reduce((s, c) => s + Number(c.valor), 0);
    const vencidos = lista.filter(c => c.estado === 'vencido').length;

    badge.textContent = lista.length + (vencidos ? ` · ${vencidos} vencido${vencidos>1?'s':''}` : '');
    badge.classList.remove('hidden');
    if (vencidos) {
        badge.style.backgroundColor = '#7f1d1d';
        badge.style.color = '#fca5a5';
        badge.style.borderColor = '#ef4444';
    } else {
        badge.style.backgroundColor = '#3a2a0f';
        badge.style.color = '#fbbf24';
        badge.style.borderColor = '#f59e0b';
    }
    resumen.textContent = `Cartera: $${Math.round(total).toLocaleString('es-CO')}`;

    el.innerHTML = lista.map(c => {
        const esVencido = c.estado === 'vencido';
        const color     = esVencido ? '#fca5a5' : '#fbbf24';
        const bg        = esVencido ? 'rgba(127,29,29,.35)' : 'rgba(58,42,15,.35)';
        const fecha     = c.fecha_compromiso_pago ? c.fecha_compromiso_pago.substring(0,10) : '—';
        return `<div class="flex items-center justify-between px-3 py-2 gap-2 border-b"
                     style="border-color:var(--rojo-bord); background-color:${bg};">
            <div class="min-w-0 flex-1">
                <p class="font-bold text-xs truncate" style="color:${color};">
                    ${escapeHtml(c.nombre_empleado)}
                    ${esVencido ? '<span style="font-size:.65rem; background:#7f1d1d; color:#fca5a5; padding:1px 5px; border-radius:99px; margin-left:4px;">VENCIDO</span>' : ''}
                </p>
                <p class="text-xs" style="color:#6b7280;">Hasta: ${fecha}</p>
            </div>
            <div class="text-right shrink-0">
                <p class="font-black text-xs" style="color:${color};">$${Math.round(Number(c.valor)).toLocaleString('es-CO')}</p>
                <button onclick="pagarCredito(${c.id})"
                        class="text-xs font-bold px-2 py-0.5 rounded mt-0.5 transition-all"
                        style="background-color:#134e2a; color:#4ade80; border:1px solid #16a34a;">
                    ✓ Pagar
                </button>
            </div>
        </div>`;
    }).join('');
}

async function crearCredito() {
    const empleadoId = document.getElementById('creditoEmpleado').value;
    const valor      = document.getElementById('creditoValor').value;
    const fecha      = document.getElementById('creditoFecha').value;
    const obs        = document.getElementById('creditoObs').value.trim();
    const alerta     = document.getElementById('alertaCredito');
    const btn        = document.getElementById('btnCrearCredito');

    if (!empleadoId || !valor || !fecha) {
        mostrarAlerta(alerta, '⚠️ Completa empleado, valor y fecha', 'err');
        return;
    }

    btn.disabled = true;
    btn.textContent = '⏳ Registrando...';

    try {
        const res  = await fetch('/creditos/crear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify({
                empleado_id:      parseInt(empleadoId),
                valor:            parseFloat(valor),
                fecha_prestamo:   new Date().toISOString().substring(0,10),
                fecha_compromiso: fecha,
                observaciones:    obs,
            }),
        });
        const data = await res.json();
        if (data.status === 'ok') {
            mostrarAlerta(alerta, '✅ Préstamo registrado', 'ok');
            document.getElementById('creditoEmpleado').value = '';
            document.getElementById('creditoValor').value    = '';
            document.getElementById('creditoFecha').value    = '';
            document.getElementById('creditoObs').value      = '';
            await cargarCreditos();
            await refrescarCaja();
        } else {
            mostrarAlerta(alerta, `⚠️ ${data.mensaje ?? 'Error'}`, 'err');
        }
    } catch {
        mostrarAlerta(alerta, '⚠️ Error de conexión', 'err');
    } finally {
        btn.disabled = false;
        btn.textContent = '💳 Registrar préstamo';
    }
}

async function pagarCredito(id) {
    if (!confirm('¿Confirmar pago de este préstamo?')) return;
    try {
        const res  = await fetch('/creditos/pagar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify({ id }),
        });
        const data = await res.json();
        if (data.status === 'ok') {
            await cargarCreditos();
            await refrescarCaja();
        } else {
            alert(data.mensaje ?? 'Error al registrar pago');
        }
    } catch {
        alert('Error de conexión al registrar pago');
    }
}

document.addEventListener('DOMContentLoaded', () => cargarCreditos());
</script>
<?php endif; ?>

</body>
</html>

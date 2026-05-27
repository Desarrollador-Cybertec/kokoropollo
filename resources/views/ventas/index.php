<?php
declare(strict_types=1);
use App\Core\View;

$pageTitle = 'Ventas — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<style>
/* Aliases usados por ventas.js al manipular estilos inline */
:root {
    --rojo-alt:  #4a0e0e;
    --rojo-bord: #5a1a1a;
    --oro-cl:    #e6c857;
}
body { background-color: var(--rojo-deep); min-height: 100vh; }

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
</style>
<body class="py-6 pb-28">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-4xl mx-auto px-4 space-y-5">

    <!-- Encabezado -->
    <div class="flex items-center justify-between">
        <a href="<?= View::escape($dashboardUrl) ?>"
           class="font-black text-base px-5 py-3 rounded-xl btn-primary">
            ← REGRESAR
        </a>
        <h1 class="font-black text-3xl tracking-wide" style="color:var(--oro);">🛒 Ventas</h1>
        <div class="w-28"></div>
    </div>

    <!-- ══════════════════════════════════════════
         BLOQUE 1 — Seleccionar producto
    ══════════════════════════════════════════ -->
    <div class="rounded-2xl p-5 shadow-xl" style="background-color:var(--rojo-card);">
        <h2 class="font-black text-xl mb-4" style="color:var(--oro);">1️⃣ Seleccionar producto</h2>

        <!-- Filtros de categoría -->
        <div class="flex flex-wrap gap-2 mb-4" id="catFiltros">
            <button class="cat-btn activa" data-cat="Todos" onclick="filtrarCategoria(this)">
                🔎 Todos
            </button>
            <?php foreach ($categoriasConfig as $cat => $cfg): ?>
                <button class="cat-btn" data-cat="<?= $cat ?>" onclick="filtrarCategoria(this)">
                    <?= $cfg['emoji'] ?> <?= $cat ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de tarjetas de producto -->
        <div id="gridProductos" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php foreach ($productos as $p): ?>
                <div class="prod-card <?= (int)$p['cantidad'] <= 0 ? 'sin-stock' : '' ?>"
                     data-id="<?= (int)$p['id'] ?>"
                     data-nombre="<?= View::escape($p['articulo']) ?>"
                     data-precio="<?= (float)$p['valor'] ?>"
                     data-stock="<?= (int)$p['cantidad'] ?>"
                     data-cat="<?= View::escape($p['categoria']) ?>"
                     onclick="seleccionarProducto(this)">
                    <div class="cat-emoji">
                        <?= $categoriasConfig[$p['categoria']]['emoji'] ?? '📦' ?>
                    </div>
                    <div class="prod-nom"><?= View::escape($p['articulo']) ?></div>
                    <div class="prod-prec">$<?= number_format((float)$p['valor'], 0, ',', '.') ?></div>
                    <div class="prod-stk <?= (int)$p['cantidad'] <= 5 ? 'text-red-400' : 'text-green-400' ?>">
                        <?= (int)$p['cantidad'] ?> disponibles
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($productos)): ?>
                <div class="col-span-4 text-center py-8" style="color:#9ca3af;">
                    Sin productos con stock disponible
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         BLOQUE 2 — Configurar (corte + cantidad)
    ══════════════════════════════════════════ -->
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

        <!-- Corte (solo para Asado / Broaster) -->
        <div id="seccionCorte" class="hidden mb-5">
            <p class="text-sm font-bold mb-3" style="color:#9ca3af;">¿Cuánto lleva?</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <button type="button" class="corte-btn" data-mult="1" onclick="seleccionarCorte(this,'1/4 Pierna-Pernil')">
                    <span class="ce">🍗</span>
                    <span>¼ Pierna</span>
                    <span class="cs">Pierna + Pernil</span>
                    <span class="cs">× 1 ud</span>
                </button>
                <button type="button" class="corte-btn" data-mult="1" onclick="seleccionarCorte(this,'1/4 Pechuga-Ala')">
                    <span class="ce">🍗</span>
                    <span>¼ Pechuga</span>
                    <span class="cs">Pechuga + Ala</span>
                    <span class="cs">× 1 ud</span>
                </button>
                <button type="button" class="corte-btn" data-mult="2" onclick="seleccionarCorte(this,'Medio Pollo')">
                    <span class="ce">🍗🍗</span>
                    <span>Medio Pollo</span>
                    <span class="cs">2 cuartos</span>
                    <span class="cs">× 2 uds</span>
                </button>
                <button type="button" class="corte-btn" data-mult="4" onclick="seleccionarCorte(this,'Pollo Entero')">
                    <span class="ce">🐔</span>
                    <span>Pollo Entero</span>
                    <span class="cs">4 cuartos</span>
                    <span class="cs">× 4 uds</span>
                </button>
            </div>
        </div>

        <!-- Cantidad + agregar -->
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
                        class="font-black text-xl px-8 py-4 rounded-2xl shadow-lg btn-green"
                        disabled>
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

    <!-- ══════════════════════════════════════════
         BLOQUE 2.5 — Acompañamientos rápidos
    ══════════════════════════════════════════ -->
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

    <!-- ══════════════════════════════════════════
         BLOQUE 3 — Carrito del pedido
    ══════════════════════════════════════════ -->
    <div id="seccionCarrito" class="hidden rounded-2xl p-5 shadow-xl" style="background-color:var(--rojo-card);">
        <h2 class="font-black text-xl mb-4" style="color:var(--oro);">
            🧺 Pedido actual
            <span id="cantItems" class="text-base font-semibold text-gray-400"></span>
        </h2>

        <div id="listaCarrito" class="space-y-2 mb-5"></div>

        <!-- Total del pedido -->
        <div class="flex justify-between items-center rounded-xl px-5 py-4 mb-4"
             style="background-color:var(--rojo-deep); border:2px solid var(--rojo-bord);">
            <span class="font-bold text-lg text-gray-300">TOTAL DEL PEDIDO</span>
            <span id="totalPedido" class="font-black text-4xl" style="color:var(--oro);">$0</span>
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

    <!-- ══════════════════════════════════════════
         BLOQUE 4 — Historial del día
    ══════════════════════════════════════════ -->
    <div id="seccionHistorial" class="hidden space-y-3">
        <div class="flex justify-between items-center">
            <h2 class="font-black text-2xl" style="color:var(--oro);">📊 Pedidos de hoy</h2>
            <button id="btnFactura" onclick="generarFactura()"
                    class="font-black text-base px-5 py-3 rounded-xl btn-blue">
                🧾 Factura del día
            </button>
        </div>

        <div id="listaPedidos" class="space-y-3"></div>

        <div class="px-6 py-4 rounded-xl text-right font-black text-2xl"
             style="background-color:var(--rojo-alt); color:var(--oro); border:2px solid var(--rojo-bord);">
            Total del día: $<span id="totalDiaSpan"><?= number_format((float)$totalDia, 0, ',', '.') ?></span>
        </div>
    </div>

</div>

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
const PRODUCTOS    = <?= $productosJson ?>;
let totalDiaAcum   = <?= (float)$totalDia ?>;
</script>
<script src="/js/ventas.js"></script>

</body>
</html>

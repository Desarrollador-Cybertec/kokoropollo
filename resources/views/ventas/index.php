<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

// Agrupar productos por categoría para pasarlos al JS
$productosJson = json_encode(array_values($productos), JSON_UNESCAPED_UNICODE);
$categoriasConfig = [
    'Asado'           => ['emoji' => '🍗', 'label' => 'Asado'],
    'Broaster'        => ['emoji' => '🍳', 'label' => 'Broaster'],
    'Papas'           => ['emoji' => '🥔', 'label' => 'Papas'],
    'Acompañamientos' => ['emoji' => '🍌', 'label' => 'Acompañ.'],
    'Salsas'          => ['emoji' => '🫙', 'label' => 'Salsas'],
    'Bebidas'         => ['emoji' => '🥤', 'label' => 'Bebidas'],
    'Otros'           => ['emoji' => '📦', 'label' => 'Otros'],
];
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas — Kokoro Pollo</title>
    <?= Csrf::meta() ?>
    <script src="https://cdn.tailwindcss.com"></script>
<style>
:root {
    --rojo:      #3b0a0a;
    --rojo-alt:  #4a0e0e;
    --rojo-card: #3c1f1f;
    --rojo-deep: #2b1a1a;
    --rojo-bord: #5a1a1a;
    --oro:       #d4af37;
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
.q-btn:hover { background-color: var(--oro); color: var(--rojo); }
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

/* ── Select producto (fallback accesibilidad) ── */
.select-accesible {
    width: 100%;
    background-color: var(--rojo-deep); color: var(--oro);
    border: 2px solid var(--rojo-bord); border-radius: .75rem;
    padding: .9rem 1rem; font-size: 1.1rem; font-weight: 600; outline: none;
}
.select-accesible:focus { border-color: var(--oro); }
</style>
</head>
<body class="py-6 pb-28">

<div class="max-w-4xl mx-auto px-4 space-y-5">

    <!-- Encabezado -->
    <div class="flex items-center justify-between">
        <button onclick="window.location.href='<?= View::escape($dashboardUrl) ?>'"
                class="font-black text-base px-5 py-3 rounded-xl"
                style="background-color:var(--oro);color:var(--rojo);"
                onmouseover="this.style.backgroundColor='var(--oro-cl)'"
                onmouseout="this.style.backgroundColor='var(--oro)'">
            ← REGRESAR
        </button>
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
                        class="font-black text-xl px-8 py-4 rounded-2xl shadow-lg transition-all disabled:opacity-40"
                        style="background-color:#16a34a; color:white;"
                        onmouseover="if(!this.disabled)this.style.backgroundColor='#15803d'"
                        onmouseout="if(!this.disabled)this.style.backgroundColor='#16a34a'"
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
                class="w-full font-black text-xl py-4 rounded-2xl transition-all"
                style="background-color:#16a34a; color:white;"
                onmouseover="this.style.backgroundColor='#15803d'"
                onmouseout="this.style.backgroundColor='#16a34a'">
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
                    class="flex-1 font-black text-xl py-5 rounded-2xl shadow-xl transition-all uppercase tracking-wide"
                    style="background-color:#16a34a; color:white; min-width:200px;"
                    onmouseover="this.style.backgroundColor='#15803d'"
                    onmouseout="this.style.backgroundColor='#16a34a'">
                ✅ Registrar Pedido
            </button>
            <button onclick="vaciarCarrito()"
                    class="font-bold text-lg px-6 py-5 rounded-2xl transition-all"
                    style="background-color:var(--rojo-bord); color:#fca5a5;"
                    onmouseover="this.style.backgroundColor='#7f1d1d'"
                    onmouseout="this.style.backgroundColor='var(--rojo-bord)'">
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
                    class="font-black text-base px-5 py-3 rounded-xl transition-all"
                    style="background-color:#1d4ed8; color:white;"
                    onmouseover="this.style.backgroundColor='#1e40af'"
                    onmouseout="this.style.backgroundColor='#1d4ed8'">
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
                class="mt-6 w-full font-black text-xl py-4 rounded-xl transition-all"
                style="background-color:var(--oro);color:var(--rojo);"
                onmouseover="this.style.backgroundColor='var(--oro-cl)'"
                onmouseout="this.style.backgroundColor='var(--oro)'">
            🖨️ Imprimir
        </button>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ── Estado global ── */
const PRODUCTOS = <?= $productosJson ?>;
let prodSeleccionado = null;   // { id, nombre, precio, stock, cat }
let corteActual      = null;   // { nombre, mult }
let carrito          = [];     // [{ id, nombre, corte, cantForm, cantInv, precio, subtotal }]
let totalDiaAcum     = <?= (float)$totalDia ?>;
let pedidosHoy       = [];     // para el historial visual

/* ── Helpers ── */
function fmt(n) { return Math.round(n).toLocaleString('es-CO'); }
function genOrdenId() {
    return Date.now().toString(36).slice(-6).toUpperCase() +
           Math.random().toString(36).slice(2,6).toUpperCase();
}

/* ── Filtrar por categoría ── */
function filtrarCategoria(btn) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('activa'));
    btn.classList.add('activa');
    const cat = btn.dataset.cat;
    document.querySelectorAll('.prod-card').forEach(c => {
        c.style.display = (cat === 'Todos' || c.dataset.cat === cat) ? '' : 'none';
    });
}

/* ── Seleccionar producto ── */
function seleccionarProducto(card) {
    if (card.classList.contains('sin-stock')) return;
    document.querySelectorAll('.prod-card').forEach(c => c.classList.remove('activa'));
    card.classList.add('activa');

    prodSeleccionado = {
        id:     parseInt(card.dataset.id),
        nombre: card.dataset.nombre,
        precio: parseFloat(card.dataset.precio),
        stock:  parseInt(card.dataset.stock),
        cat:    card.dataset.cat,
    };
    corteActual = null;

    document.getElementById('cfgNombre').textContent = prodSeleccionado.nombre;
    document.getElementById('cfgPrecio').textContent =
        '$' + fmt(prodSeleccionado.precio) + ' / unidad · Stock: ' + prodSeleccionado.stock + ' uds';
    document.getElementById('cfgCantidad').value = 1;
    document.getElementById('cfgSubtotal').textContent = '$0';
    document.getElementById('alertaStockCfg').classList.add('hidden');
    document.getElementById('btnAgregar').disabled = true;

    const esPollo = prodSeleccionado.cat === 'Asado' || prodSeleccionado.cat === 'Broaster';
    const secCorte = document.getElementById('seccionCorte');
    secCorte.classList.toggle('hidden', !esPollo);

    if (!esPollo) {
        corteActual = { nombre: 'Unidad', mult: 1 };
        document.querySelectorAll('.corte-btn').forEach(b => b.classList.remove('activo'));
        recalcSubtotal();
    }

    document.getElementById('configPanel').classList.remove('hidden');
    document.getElementById('configPanel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function deseleccionarProducto() {
    prodSeleccionado = null; corteActual = null;
    document.querySelectorAll('.prod-card').forEach(c => c.classList.remove('activa'));
    document.querySelectorAll('.corte-btn').forEach(b => b.classList.remove('activo'));
    document.getElementById('configPanel').classList.add('hidden');
}

/* ── Seleccionar corte ── */
function seleccionarCorte(btn, nombre) {
    document.querySelectorAll('.corte-btn').forEach(b => b.classList.remove('activo'));
    btn.classList.add('activo');
    corteActual = { nombre, mult: parseInt(btn.dataset.mult) };
    recalcSubtotal();
}

/* ── Recalcular subtotal ── */
function recalcSubtotal() {
    if (!prodSeleccionado || !corteActual) return;
    const cant    = Math.max(1, parseInt(document.getElementById('cfgCantidad').value) || 1);
    const cantInv = cant * corteActual.mult;
    const sub     = prodSeleccionado.precio * cantInv;

    // Calcular stock ya reservado en carrito para este producto
    const reservado = carrito.filter(i => i.id === prodSeleccionado.id)
                             .reduce((s, i) => s + i.cantInv, 0);
    const disponible = prodSeleccionado.stock - reservado;

    const sinStock = cantInv > disponible;
    document.getElementById('alertaStockCfg').classList.toggle('hidden', !sinStock);
    document.getElementById('cfgSubtotal').textContent = sinStock ? '—' : '$' + fmt(sub);
    document.getElementById('btnAgregar').disabled = sinStock;
}

function cambiarCantidad(delta) {
    const inp = document.getElementById('cfgCantidad');
    inp.value = Math.max(1, (parseInt(inp.value) || 1) + delta);
    recalcSubtotal();
}

/* ── Agregar al carrito ── */
function agregarAlCarrito() {
    if (!prodSeleccionado || !corteActual) return;
    const cant    = Math.max(1, parseInt(document.getElementById('cfgCantidad').value) || 1);
    const cantInv = cant * corteActual.mult;
    const sub     = prodSeleccionado.precio * cantInv;
    const esPollo = prodSeleccionado.cat === 'Asado' || prodSeleccionado.cat === 'Broaster';

    carrito.push({
        uid:      Math.random().toString(36).slice(2, 8),
        id:       prodSeleccionado.id,
        nombre:   prodSeleccionado.nombre,
        corte:    corteActual.nombre,
        cantForm: cant,
        cantInv:  cantInv,
        precio:   prodSeleccionado.precio,
        subtotal: sub,
    });

    deseleccionarProducto();

    if (esPollo) {
        mostrarAcomp();
    } else {
        renderCarrito();
    }
}

/* ── Acompañamientos rápidos ── */
const CAT_EMOJI = { Asado:'🍗', Broaster:'🍳', Papas:'🥔', 'Acompañamientos':'🍌', Salsas:'🫙', Bebidas:'🥤', Otros:'📦' };
let acompSeleccionados = new Set();

function mostrarAcomp() {
    const disponibles = PRODUCTOS.filter(p =>
        ['Papas', 'Acompañamientos', 'Salsas', 'Bebidas'].includes(p.categoria) && p.cantidad > 0
    );

    if (disponibles.length === 0) { renderCarrito(); return; }

    acompSeleccionados = new Set();
    const reservados = {};
    carrito.forEach(i => { reservados[i.id] = (reservados[i.id] || 0) + i.cantInv; });

    document.getElementById('gridAcomp').innerHTML = disponibles.map(p => {
        const stock = p.cantidad - (reservados[p.id] || 0);
        if (stock <= 0) return '';
        return `
        <button class="acomp-toggle" data-id="${p.id}" data-nombre="${p.articulo}"
                data-precio="${p.valor}"
                onclick="toggleAcomp(this)"
                style="background-color:var(--rojo-deep); border:2px solid var(--rojo-bord);
                       border-radius:.75rem; padding:.9rem .5rem; text-align:center; cursor:pointer;
                       transition:all .18s; width:100%;">
            <div style="font-size:1.6rem;">${CAT_EMOJI[p.categoria] ?? '📦'}</div>
            <div style="font-weight:800; color:white; font-size:.9rem; margin:.25rem 0; line-height:1.2;">${p.articulo}</div>
            <div style="font-weight:700; color:var(--oro); font-size:.85rem;">$${fmt(p.valor)}</div>
        </button>`;
    }).join('');

    document.getElementById('seccionAcomp').classList.remove('hidden');
    document.getElementById('seccionAcomp').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function toggleAcomp(btn) {
    const id = btn.dataset.id;
    if (acompSeleccionados.has(id)) {
        acompSeleccionados.delete(id);
        btn.style.borderColor       = 'var(--rojo-bord)';
        btn.style.backgroundColor   = 'var(--rojo-deep)';
        btn.style.boxShadow         = 'none';
    } else {
        acompSeleccionados.add(id);
        btn.style.borderColor       = 'var(--oro)';
        btn.style.backgroundColor   = 'var(--rojo-alt)';
        btn.style.boxShadow         = '0 0 0 3px var(--oro)';
    }
}

function agregarAcompSeleccionados() {
    document.querySelectorAll('.acomp-toggle').forEach(btn => {
        if (!acompSeleccionados.has(btn.dataset.id)) return;
        carrito.push({
            uid:      Math.random().toString(36).slice(2, 8),
            id:       parseInt(btn.dataset.id),
            nombre:   btn.dataset.nombre,
            corte:    'Unidad',
            cantForm: 1,
            cantInv:  1,
            precio:   parseFloat(btn.dataset.precio),
            subtotal: parseFloat(btn.dataset.precio),
        });
    });
    cerrarAcomp();
}

function cerrarAcomp() {
    document.getElementById('seccionAcomp').classList.add('hidden');
    acompSeleccionados = new Set();
    renderCarrito();
}

/* ── Render carrito ── */
function renderCarrito() {
    const lista = document.getElementById('listaCarrito');
    const total = carrito.reduce((s, i) => s + i.subtotal, 0);

    if (carrito.length === 0) {
        document.getElementById('seccionCarrito').classList.add('hidden');
        return;
    }

    document.getElementById('seccionCarrito').classList.remove('hidden');
    document.getElementById('cantItems').textContent = '(' + carrito.length + ' ítem' + (carrito.length > 1 ? 's' : '') + ')';
    document.getElementById('totalPedido').textContent = '$' + fmt(total);

    lista.innerHTML = carrito.map(item => `
        <div class="carrito-item">
            <div class="flex-1 min-w-0">
                <p class="font-bold text-white text-base leading-tight">${item.nombre}</p>
                <p class="text-sm" style="color:#9ca3af;">
                    ${item.corte !== 'Unidad' ? item.corte + ' · ' : ''}
                    ${item.cantForm} × $${fmt(item.precio)}
                    ${item.cantInv > item.cantForm ? '(descuenta ' + item.cantInv + ' uds)' : ''}
                </p>
            </div>
            <span class="font-black text-lg whitespace-nowrap" style="color:var(--oro);">
                $${fmt(item.subtotal)}
            </span>
            <button onclick="quitarItem('${item.uid}')"
                    class="ml-1 text-red-400 hover:text-red-300 font-black text-xl leading-none transition-all">
                ×
            </button>
        </div>
    `).join('');
}

function quitarItem(uid) {
    carrito = carrito.filter(i => i.uid !== uid);
    renderCarrito();
}

function vaciarCarrito() {
    carrito = [];
    renderCarrito();
    document.getElementById('alertaRegistro').classList.add('hidden');
}

/* ── Registrar pedido ── */
async function registrarPedido() {
    if (carrito.length === 0) return;
    const ordenId   = genOrdenId();
    const btn       = document.getElementById('btnRegistrar');
    const alerta    = document.getElementById('alertaRegistro');
    btn.disabled    = true;
    btn.textContent = '⏳ Registrando...';
    alerta.classList.add('hidden');

    const errores = [];
    const registrados = [];
    let totalPedido = 0;

    for (const item of carrito) {
        try {
            const res = await fetch('/ventas/store', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                body: JSON.stringify({
                    orden_id:        ordenId,
                    inventario_id:   item.id,
                    cantidad:        item.cantInv,
                    precio_unitario: item.precio,
                }),
            });
            const data = await res.json();
            if (data.status !== 'ok') {
                errores.push(`${item.nombre}: ${data.mensaje ?? 'error'}`);
            } else {
                registrados.push(item);
                totalPedido += item.subtotal;
                // Actualizar stock visual en las tarjetas
                actualizarStockCard(item.id, item.cantInv);
            }
        } catch {
            errores.push(`${item.nombre}: error de conexión`);
        }
    }

    btn.disabled    = false;
    btn.textContent = '✅ Registrar Pedido';

    if (errores.length > 0) {
        alerta.innerHTML = '⚠️ Errores:<br>' + errores.join('<br>');
        alerta.classList.remove('hidden');
        // Remover solo los que se registraron exitosamente
        const uidsOk = registrados.map(i => i.uid);
        carrito = carrito.filter(i => !uidsOk.includes(i.uid));
        renderCarrito();
    } else {
        // Todo OK
        carrito = [];
        renderCarrito();
    }

    if (registrados.length > 0) {
        totalDiaAcum += totalPedido;
        document.getElementById('totalDiaSpan').textContent = fmt(totalDiaAcum);
        agregarPedidoAlHistorial(ordenId, registrados, totalPedido);
    }
}

function actualizarStockCard(prodId, cantDeducida) {
    const card = document.querySelector(`.prod-card[data-id="${prodId}"]`);
    if (!card) return;
    let stock = parseInt(card.dataset.stock) - cantDeducida;
    if (stock < 0) stock = 0;
    card.dataset.stock = stock;
    const stkEl = card.querySelector('.prod-stk');
    if (stkEl) {
        stkEl.textContent = stock + ' disponibles';
        stkEl.className = 'prod-stk ' + (stock <= 5 ? 'text-red-400' : 'text-green-400');
    }
    if (stock <= 0) {
        card.classList.add('sin-stock');
    }
}

/* ── Historial visual del día ── */
function agregarPedidoAlHistorial(ordenId, items, total) {
    pedidosHoy.unshift({ ordenId, items: [...items], total });
    renderHistorial();
    document.getElementById('seccionHistorial').classList.remove('hidden');
}

function renderHistorial() {
    const lista = document.getElementById('listaPedidos');
    lista.innerHTML = pedidosHoy.map((pedido, idx) => `
        <div class="rounded-xl overflow-hidden" style="border:1px solid var(--rojo-bord);">
            <button onclick="togglePedido(${idx})"
                    class="w-full flex justify-between items-center px-4 py-3 font-bold text-left transition-all"
                    style="background-color:var(--rojo-alt); color:var(--oro);">
                <span>📦 Pedido #${pedido.ordenId} — ${pedido.items.length} ítem(s)</span>
                <span>$${fmt(pedido.total)} ▾</span>
            </button>
            <div id="pedido-${idx}" class="hidden">
                ${pedido.items.map(i => `
                    <div class="flex justify-between items-center px-4 py-2 text-sm"
                         style="border-top:1px solid var(--rojo-bord); color:#e5e7eb;">
                        <span class="font-semibold">${i.nombre}
                            ${i.corte !== 'Unidad' ? '<span style="color:#9ca3af;"> · ' + i.corte + '</span>' : ''}
                        </span>
                        <span style="color:var(--oro); font-weight:700;">$${fmt(i.subtotal)}</span>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');
}

function togglePedido(idx) {
    const el = document.getElementById('pedido-' + idx);
    el.classList.toggle('hidden');
}

/* ── Factura ── */
function generarFactura() {
    if (pedidosHoy.length === 0) return;
    let html = `<p style="font-size:1.2rem; font-weight:900; color:var(--oro); margin-bottom:.75rem;">
                    Kokoro Pollo — Ventas del día</p>`;
    pedidosHoy.forEach(p => {
        html += `<p style="font-weight:700; color:#d4af37; margin:.5rem 0 .25rem;">
                     Pedido #${p.ordenId}</p>`;
        p.items.forEach(i => {
            html += `<div style="display:flex;justify-content:space-between;padding:.2rem 0;border-bottom:1px solid #5a1a1a;">
                         <span>${i.nombre}${i.corte !== 'Unidad' ? ' (' + i.corte + ')' : ''} × ${i.cantForm}</span>
                         <span style="color:#d4af37;font-weight:700;">$${fmt(i.subtotal)}</span>
                     </div>`;
        });
    });
    html += `<p style="text-align:right;font-weight:900;font-size:1.3rem;margin-top:1rem;color:var(--oro);">
                 Total del día: $${fmt(totalDiaAcum)}</p>`;
    document.getElementById('contenidoFactura').innerHTML = html;
    document.getElementById('modalFactura').classList.remove('hidden');
}

function imprimirFactura() {
    const c = document.getElementById('contenidoFactura').innerHTML;
    const w = window.open('', '', 'width=800,height=600');
    w.document.write(`<html><head><title>Factura Kokoro Pollo</title>
        <style>body{font-family:sans-serif;padding:20px;color:#333}
        div{margin:.2rem 0}</style></head><body>${c}</body></html>`);
    w.document.close(); w.print();
}

document.getElementById('modalFactura').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>

</body>
</html>

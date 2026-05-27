/* ─── Kokoro Pollo — Ventas POS ─────────────────────────── */
/* Requiere que la vista inyecte antes: PRODUCTOS, totalDiaAcum */

const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ── Estado global ── */
let prodSeleccionado = null;  // { id, nombre, precio, stock, cat }
let corteActual      = null;  // { nombre, mult }
let carrito          = [];    // [{ uid, id, nombre, corte, cantForm, cantInv, precio, subtotal }]
let pedidosHoy       = [];    // historial visual del día

/* ── Helpers ── */
function fmt(n) { return Math.round(n).toLocaleString('es-CO'); }
function genOrdenId() {
    return Date.now().toString(36).slice(-6).toUpperCase() +
           Math.random().toString(36).slice(2, 6).toUpperCase();
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

    const esPollo   = prodSeleccionado.cat === 'Asado' || prodSeleccionado.cat === 'Broaster';
    const secCorte  = document.getElementById('seccionCorte');
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
    prodSeleccionado = null;
    corteActual      = null;
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

    const reservado  = carrito.filter(i => i.id === prodSeleccionado.id)
                               .reduce((s, i) => s + i.cantInv, 0);
    const disponible = prodSeleccionado.stock - reservado;
    const sinStock   = cantInv > disponible;

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
const CAT_EMOJI = {
    Asado:'🍗', Broaster:'🍳', Papas:'🥔',
    'Acompañamientos':'🍌', Salsas:'🫙', Bebidas:'🥤', Otros:'📦',
};
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
        btn.style.borderColor     = 'var(--rojo-bord)';
        btn.style.backgroundColor = 'var(--rojo-deep)';
        btn.style.boxShadow       = 'none';
    } else {
        acompSeleccionados.add(id);
        btn.style.borderColor     = 'var(--oro)';
        btn.style.backgroundColor = 'var(--rojo-alt)';
        btn.style.boxShadow       = '0 0 0 3px var(--oro)';
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
    document.getElementById('cantItems').textContent =
        '(' + carrito.length + ' ítem' + (carrito.length > 1 ? 's' : '') + ')';
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
                    class="ml-1 font-black text-xl leading-none transition-all"
                    style="color:#f87171;">×</button>
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
    const ordenId = genOrdenId();
    const btn     = document.getElementById('btnRegistrar');
    const alerta  = document.getElementById('alertaRegistro');
    btn.disabled  = true;
    btn.textContent = '⏳ Registrando...';
    alerta.classList.add('hidden');

    const errores    = [];
    const registrados = [];
    let totalPedido  = 0;

    for (const item of carrito) {
        try {
            const res = await fetch('/ventas/store', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                body:    JSON.stringify({
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
        const uidsOk = registrados.map(i => i.uid);
        carrito = carrito.filter(i => !uidsOk.includes(i.uid));
        renderCarrito();
    } else {
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
        stkEl.className   = 'prod-stk ' + (stock <= 5 ? 'text-red-400' : 'text-green-400');
    }
    if (stock <= 0) card.classList.add('sin-stock');
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
    document.getElementById('pedido-' + idx).classList.toggle('hidden');
}

/* ── Factura ── */
function generarFactura() {
    if (pedidosHoy.length === 0) return;
    let html = `<p style="font-size:1.2rem; font-weight:900; color:var(--oro); margin-bottom:.75rem;">
                    Kokoro Pollo — Ventas del día</p>`;
    pedidosHoy.forEach(p => {
        html += `<p style="font-weight:700; color:#d4af37; margin:.5rem 0 .25rem;">Pedido #${p.ordenId}</p>`;
        p.items.forEach(i => {
            html += `<div style="display:flex; justify-content:space-between; padding:.2rem 0; border-bottom:1px solid #5a1a1a;">
                         <span>${i.nombre}${i.corte !== 'Unidad' ? ' (' + i.corte + ')' : ''} × ${i.cantForm}</span>
                         <span style="color:#d4af37; font-weight:700;">$${fmt(i.subtotal)}</span>
                     </div>`;
        });
    });
    html += `<p style="text-align:right; font-weight:900; font-size:1.3rem; margin-top:1rem; color:var(--oro);">
                 Total del día: $${fmt(totalDiaAcum)}</p>`;
    document.getElementById('contenidoFactura').innerHTML = html;
    document.getElementById('modalFactura').classList.remove('hidden');
}

function imprimirFactura() {
    const c = document.getElementById('contenidoFactura').innerHTML;
    const w = window.open('', '', 'width=800,height=600');
    w.document.write(`<html><head><title>Factura Kokoro Pollo</title>
        <style>body{font-family:sans-serif;padding:20px;color:#333} div{margin:.2rem 0}</style>
        </head><body>${c}</body></html>`);
    w.document.close();
    w.print();
}

document.getElementById('modalFactura').addEventListener('click', function (e) {
    if (e.target === this) this.classList.add('hidden');
});

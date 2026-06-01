/* ─── Kokoro Pollo — Ventas POS ─────────────────────────── */
/* Requiere que la vista inyecte antes: PRODUCTOS, totalDiaAcum */

const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ── Estado global ── */
let prodSeleccionado  = null;    // { id, nombre, precio, stock, cat }
let corteActual       = null;    // { nombre, mult }
let carrito           = [];      // [{ uid, id, nombre, ... }]
let pedidosHoy        = [];      // historial visual del día
let tipoPedidoActual  = 'local'; // 'local' | 'llevar'

/* ── Helpers ── */
function fmt(n) { return Math.round(n).toLocaleString('es-CO'); }
function genOrdenId() {
    return Date.now().toString(36).slice(-6).toUpperCase() +
           Math.random().toString(36).slice(2, 6).toUpperCase();
}

/* ── Tipo de pedido ── */
function seleccionarTipo(btn) {
    document.querySelectorAll('.tipo-btn').forEach(b => {
        b.classList.remove('activa', 'llevar-activa');
    });
    tipoPedidoActual = btn.dataset.tipo;
    const esLlevar   = tipoPedidoActual === 'llevar';
    btn.classList.add(esLlevar ? 'llevar-activa' : 'activa');

    const panel = document.getElementById('panelCliente');
    const badge = document.getElementById('badgeTipo');
    if (panel) panel.classList.toggle('hidden', !esLlevar);
    if (badge) badge.classList.toggle('hidden', !esLlevar);
}

function getDatosCliente() {
    if (tipoPedidoActual !== 'llevar') return {};
    return {
        nombre_cliente: document.getElementById('clienteNombre')?.value.trim()   || null,
        telefono:       document.getElementById('clienteTelefono')?.value.trim() || null,
        direccion:      document.getElementById('clienteDireccion')?.value.trim()|| null,
    };
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

    const esPolloCrudo = prodSeleccionado.cat === 'Pollo Crudo';
    const stockTxt = esPolloCrudo
        ? `Stock: ${Math.floor(prodSeleccionado.stock / 4)} pollos (${prodSeleccionado.stock} cuartos)`
        : `Stock: ${prodSeleccionado.stock} uds`;
    const precioTxt = esPolloCrudo
        ? `$${fmt(prodSeleccionado.precio * 4)} costo base/pollo`
        : `$${fmt(prodSeleccionado.precio)} / unidad`;

    document.getElementById('cfgNombre').textContent = prodSeleccionado.nombre;
    document.getElementById('cfgPrecio').textContent = precioTxt + ' · ' + stockTxt;
    document.getElementById('cfgCantidad').value = 1;
    document.getElementById('cfgSubtotal').textContent = '$0';
    document.getElementById('alertaStockCfg').classList.add('hidden');
    document.getElementById('btnAgregar').disabled = true;

    const secCorte = document.getElementById('seccionCorte');
    secCorte.classList.toggle('hidden', !esPolloCrudo);
    document.querySelectorAll('.corte-btn').forEach(b => b.classList.remove('activo'));

    if (!esPolloCrudo) {
        corteActual = { nombre: 'Unidad', mult: 1 };
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
    if (!prodSeleccionado) return;

    document.querySelectorAll('#seccionCorte .corte-btn').forEach(b => b.classList.remove('activo'));
    btn.classList.add('activo');
    const mult     = parseInt(btn.dataset.mult);
    const corteKey = btn.dataset.corteKey ?? null;

    // Precio configurado para este corte (si existe y es > 0)
    let precioCorte = null;
    if (corteKey && PRECIOS_POLLO[corteKey] > 0) {
        precioCorte = PRECIOS_POLLO[corteKey];
    }

    corteActual = { nombre, mult, precioCorte };
    recalcSubtotal();
}

/* ── Recalcular subtotal ── */
function recalcSubtotal() {
    if (!prodSeleccionado || !corteActual) return;
    const cant    = Math.max(1, parseInt(document.getElementById('cfgCantidad').value) || 1);
    const cantInv = cant * corteActual.mult;

    // Precio configurado: sub = precioCorte × cant (no × cantInv)
    // Sin config: sub = precio_por_cuarto × cantInv
    const sub = corteActual.precioCorte
        ? corteActual.precioCorte * cant
        : prodSeleccionado.precio * cantInv;

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
    const esPollo = prodSeleccionado.cat === 'Pollo Crudo';

    let sub, precio;
    if (corteActual.precioCorte) {
        sub    = corteActual.precioCorte * cant;
        precio = corteActual.precioCorte / corteActual.mult;
    } else {
        sub    = prodSeleccionado.precio * cantInv;
        precio = prodSeleccionado.precio;
    }

    const costoUnit      = prodSeleccionado.precio * corteActual.mult;
    const costoSubtotal  = costoUnit * cant;
    const margenSubtotal = sub - costoSubtotal;
    const nombreVenta    = esPollo
        ? `Asado — ${corteActual.nombre}`
        : prodSeleccionado.nombre;

    carrito.push({
        uid:      Math.random().toString(36).slice(2, 8),
        id:       prodSeleccionado.id,
        nombre:   nombreVenta,
        corte:    corteActual.nombre,
        cantForm: cant,
        cantInv:  cantInv,
        precio:   precio,
        subtotal: sub,
        costoUnit,
        costoSubtotal,
        margenSubtotal,
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
    'Pollo Crudo':'🐔', Papas:'🥔',
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
            costoUnit: parseFloat(btn.dataset.precio),
            costoSubtotal: parseFloat(btn.dataset.precio),
            margenSubtotal: 0,
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
    const totalCosto = carrito.reduce((s, i) => s + (i.costoSubtotal || 0), 0);
    const totalMargen = total - totalCosto;

    if (carrito.length === 0) {
        document.getElementById('seccionCarrito').classList.add('hidden');
        return;
    }

    document.getElementById('seccionCarrito').classList.remove('hidden');
    document.getElementById('cantItems').textContent =
        '(' + carrito.length + ' ítem' + (carrito.length > 1 ? 's' : '') + ')';
    document.getElementById('totalPedido').textContent = '$' + fmt(total);
    document.getElementById('resumenVenta').textContent = '$' + fmt(total);
    document.getElementById('resumenCosto').textContent = '$' + fmt(totalCosto);
    document.getElementById('resumenMargen').textContent = '$' + fmt(totalMargen);

    lista.innerHTML = carrito.map(item => `
        <div class="carrito-item">
            <div class="flex-1 min-w-0">
                <p class="font-bold text-white text-base leading-tight">${item.nombre}</p>
                <p class="text-sm" style="color:#9ca3af;">
                    ${item.corte !== 'Unidad' ? item.corte + ' · ' : ''}
                    ${item.cantForm} × $${fmt(item.precio)}
                    ${item.cantInv > item.cantForm ? '(descuenta ' + item.cantInv + ' uds)' : ''}
                </p>
                <p class="text-xs" style="color:#cbd5e1;">
                    Costo: $${fmt(item.costoSubtotal || 0)} · Margen: $${fmt(item.margenSubtotal || 0)}
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

    const errores     = [];
    const registrados = [];
    let totalPedido   = 0;
    let tipoPedidoRegistrado = tipoPedidoActual;
    let datosClienteRegistrados = getDatosCliente();

    for (let idx = 0; idx < carrito.length; idx++) {
        const item    = carrito[idx];
        const esVirtual = item.id < 0;
        try {
            const res = await fetch('/ventas/store', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                body:    JSON.stringify({
                    orden_id:         ordenId,
                    inventario_id:    esVirtual ? null : item.id,
                    item_descripcion: esVirtual ? item.nombre : null,
                    cantidad:         item.cantInv,
                    precio_unitario:  item.precio,
                    tipo_pedido:      tipoPedidoActual,
                    primer_item:      idx === 0,
                    ...getDatosCliente(),
                }),
            });
            const data = await res.json();
            if (data.status !== 'ok') {
                errores.push(`${item.nombre}: ${data.mensaje ?? 'error'}`);
            } else {
                if (data.tipo_pedido === 'local' || data.tipo_pedido === 'llevar') {
                    tipoPedidoRegistrado = data.tipo_pedido;
                    datosClienteRegistrados = tipoPedidoRegistrado === 'llevar' ? getDatosCliente() : {};
                }
                registrados.push(item);
                totalPedido += item.subtotal;
                if (!esVirtual) actualizarStockCard(item.id, item.cantInv);
                if (data.empaque_id > 0) actualizarStockCard(data.empaque_id, 1);
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
        agregarPedidoAlHistorial(ordenId, registrados, totalPedido, tipoPedidoRegistrado, datosClienteRegistrados);

        pendienteAcum += totalPedido;
        actualizarPanelLiquidacion();
    }
}

function actualizarStockCard(prodId, cantDeducida) {
    const card = document.querySelector(`.prod-card[data-id="${prodId}"]`);
    if (!card) return;
    let stock = parseInt(card.dataset.stock) - cantDeducida;
    if (stock < 0) stock = 0;
    card.dataset.stock = stock;

    const esPolloCrudo = card.dataset.cat === 'Pollo Crudo';
    const stkEl = card.querySelector('.prod-stk');
    if (stkEl) {
        if (esPolloCrudo) {
            stkEl.textContent = `${Math.floor(stock / 4)} pollos (${stock} cuartos)`;
            stkEl.className = 'prod-stk ' + (stock <= 4 ? 'text-red-400' : 'text-green-400');
        } else {
            stkEl.textContent = stock + ' disponibles';
            stkEl.className = 'prod-stk ' + (stock <= 5 ? 'text-red-400' : 'text-green-400');
        }
    }

    if (stock <= 0) card.classList.add('sin-stock');
}

/* ── Historial visual del día ── */
function agregarPedidoAlHistorial(ordenId, items, total, tipoPedido = tipoPedidoActual, datosCliente = getDatosCliente()) {
    pedidosHoy.unshift({ ordenId, items: [...items], total, tipo: tipoPedido, ...datosCliente });
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
                <span>${pedido.tipo === 'llevar' ? '🛵' : '🏠'} Pedido #${pedido.ordenId} — ${pedido.items.length} ítem(s)${pedido.tipo === 'llevar' ? ' · <span style="color:#34d399;">Para llevar</span>' : ''}</span>
                <span>$${fmt(pedido.total)} ▾</span>
            </button>
            <div id="pedido-${idx}" class="hidden">
                ${pedido.tipo === 'llevar' ? `
                <div class="px-4 py-2 text-xs font-semibold flex flex-wrap gap-x-4 gap-y-1"
                     style="border-top:1px solid var(--rojo-bord); background-color:rgba(52,211,153,.07); color:#34d399;">
                    🛵 <strong>Para llevar</strong>
                    ${pedido.nombre_cliente ? `· 👤 ${pedido.nombre_cliente}` : ''}
                    ${pedido.telefono       ? `· 📞 ${pedido.telefono}` : ''}
                    ${pedido.direccion      ? `· 📍 ${pedido.direccion}` : ''}
                </div>` : ''}
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
        const tipoLabel = p.tipo === 'llevar' ? ' 🛵 Para llevar' : ' 🏠 Local';
        html += `<p style="font-weight:700; color:#d4af37; margin:.5rem 0 .1rem;">Pedido #${p.ordenId}${tipoLabel}</p>`;
        if (p.tipo === 'llevar' && (p.nombre_cliente || p.telefono || p.direccion)) {
            const info = [p.nombre_cliente, p.telefono, p.direccion].filter(Boolean).join(' · ');
            html += `<p style="font-size:.8rem; color:#34d399; margin:0 0 .25rem;">${info}</p>`;
        }
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

/* ── Panel de liquidación ── */
function actualizarPanelLiquidacion() {
    const spanEl = document.getElementById('pendienteSpan');
    const descEl = document.getElementById('pendienteDesc');
    const btnEl  = document.getElementById('btnLiquidar');
    if (!spanEl) return;
    spanEl.textContent     = fmt(pendienteAcum);
    descEl.textContent     = pendienteAcum > 0 ? 'Ventas pendientes de enviar a caja' : 'Todo está liquidado ✓';
    btnEl.disabled         = pendienteAcum <= 0;
    btnEl.style.opacity    = pendienteAcum > 0 ? '1' : '0.45';
    btnEl.style.cursor     = pendienteAcum > 0 ? 'pointer' : 'not-allowed';
}

async function liquidarVentas() {
    const btn    = document.getElementById('btnLiquidar');
    const alerta = document.getElementById('alertaLiquidacion');
    if (pendienteAcum <= 0) return;

    btn.disabled    = true;
    btn.textContent = '⏳ Enviando...';
    alerta.classList.add('hidden');

    try {
        const res  = await fetch('/ventas/liquidar', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body:    JSON.stringify({}),
        });
        const data = await res.json();

        if (data.status === 'ok') {
            pendienteAcum = 0;
            actualizarPanelLiquidacion();
            mostrarAlerta(alerta, `✅ $${fmt(data.total)} enviados a caja`, 'ok');
            await refrescarCaja();
        } else {
            mostrarAlerta(alerta, `⚠️ ${data.mensaje ?? 'Error al liquidar'}`, 'err');
            btn.disabled    = false;
            btn.textContent = '🏦 Enviar a Caja';
        }
    } catch {
        mostrarAlerta(alerta, '⚠️ Error de conexión al liquidar', 'err');
        btn.disabled    = false;
        btn.textContent = '🏦 Enviar a Caja';
    }
}

/* ── Panel de caja (derecha) ── */
function mostrarAlerta(el, texto, tipo) {
    el.textContent = texto;
    if (tipo === 'ok') {
        el.style.backgroundColor = '#134e2a';
        el.style.color           = '#9ad9b0';
        el.style.border          = '1px solid #1d6b3a';
    } else {
        el.style.backgroundColor = '#7f1d1d';
        el.style.color           = '#fca5a5';
        el.style.border          = '1px solid #ef4444';
    }
    el.classList.remove('hidden');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function horaCorta(fechaStr) {
    const d = new Date(String(fechaStr).replace(' ', 'T'));
    return Number.isNaN(d.getTime())
        ? ''
        : d.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
}

function renderCajaMovimientos(movimientos) {
    const listEl = document.getElementById('cajaMovList');
    if (!listEl) return;

    if (!Array.isArray(movimientos) || movimientos.length === 0) {
        listEl.innerHTML = '<div style="padding:1.5rem; text-align:center; color:#9ca3af;">Sin actividad hoy</div>';
        return;
    }

    const eventos = [];
    const pedidos = new Map();

    movimientos.forEach(m => {
        const esVenta = m.origen === 'ventas' && m.orden_id;
        if (!esVenta) {
            eventos.push({ tipo: 'accion', fecha: m.fecha, data: m });
            return;
        }

        const key = String(m.orden_id);
        if (!pedidos.has(key)) {
            pedidos.set(key, {
                orden_id: key,
                fecha: m.fecha,
                total: 0,
                usuario: m.usuario || '',
                tipo_pedido: m.tipo_pedido || 'local',
                nombre_cliente: m.nombre_cliente || '',
                direccion: m.direccion || '',
                liquidado: Number(m.liquidado || 0) > 0,
                items: [],
            });
        }

        const p = pedidos.get(key);
        p.total += Number(m.valor || 0);
        p.items.push(m);
        if (String(m.fecha) > String(p.fecha)) p.fecha = m.fecha;
        if (Number(m.liquidado || 0) > 0) p.liquidado = true;
    });

    pedidos.forEach(p => eventos.push({ tipo: 'pedido', fecha: p.fecha, data: p }));
    eventos.sort((a, b) => (String(a.fecha) < String(b.fecha) ? 1 : -1));

    listEl.innerHTML = eventos.map(evt => {
        if (evt.tipo === 'accion') {
            const m = evt.data;
            const esRetiro = m.tipo === 'retiro';
            const color = esRetiro ? '#fca5a5' : '#4ade80';
            const icono = esRetiro ? '▼' : '▲';
            const signo = esRetiro ? '-' : '+';
            const concepto = escapeHtml(m.concepto || (esRetiro ? 'Retiro' : 'Ingreso'));

            return `<div class="flex items-center justify-between px-3 py-2 border-b" style="border-color:var(--rojo-mid);">
                <div class="min-w-0">
                    <p class="font-bold truncate" style="color:${color};">${icono} ${concepto}</p>
                    <p class="text-xs" style="color:#6b7280;">${escapeHtml(m.usuario || '')}</p>
                </div>
                <div class="text-right ml-3 shrink-0">
                    <p class="font-black" style="color:${color};">${signo}$${fmt(m.valor || 0)}</p>
                    <p class="text-xs" style="color:#9ca3af;">${horaCorta(m.fecha)}</p>
                </div>
            </div>`;
        }

        const p = evt.data;
        const esLlevar = p.tipo_pedido === 'llevar';
        const infoCliente = esLlevar && (p.nombre_cliente || p.direccion)
            ? `<div class="px-4 py-2 text-xs" style="color:#34d399; background-color:rgba(52,211,153,.07); border-top:1px solid var(--rojo-mid);">
                    ${p.nombre_cliente ? `👤 ${escapeHtml(p.nombre_cliente)}` : ''}
                    ${p.direccion ? `${p.nombre_cliente ? ' · ' : ''}📍 ${escapeHtml(p.direccion)}` : ''}
               </div>`
            : '';

        const detalles = p.items.map(i => `<div class="flex justify-between items-center px-4 py-2 text-xs" style="border-top:1px solid var(--rojo-mid); color:#d1d5db;">
                <span class="truncate mr-2">${escapeHtml(i.concepto || '—')}</span>
                <span class="font-bold" style="color:var(--oro);">+$${fmt(i.valor || 0)}</span>
            </div>`).join('');

        return `<details class="border-b" style="border-color:var(--rojo-mid);">
            <summary class="px-3 py-2 cursor-pointer list-none flex items-center justify-between tr-dark">
                <div class="min-w-0">
                    <p class="font-black truncate" style="color:var(--oro);">
                        ${esLlevar ? '🛵' : '🏠'} Pedido #${escapeHtml(p.orden_id)}
                        ${esLlevar ? '<span style="color:#34d399; font-size:.75rem;"> · Para llevar</span>' : ''}
                        ${p.liquidado ? '<span style="color:#6b7280; font-size:.75rem;"> ✓</span>' : ''}
                    </p>
                    <p class="text-xs" style="color:#6b7280;">${p.items.length} ítem(s) · ${escapeHtml(p.usuario || '')}</p>
                </div>
                <div class="text-right ml-3 shrink-0">
                    <p class="font-black" style="color:var(--oro);">+$${fmt(p.total || 0)}</p>
                    <p class="text-xs" style="color:#9ca3af;">${horaCorta(p.fecha)}</p>
                </div>
            </summary>
            ${infoCliente}
            ${detalles}
        </details>`;
    }).join('');
}

async function refrescarCaja() {
    try {
        const res  = await fetch('/caja/resumen', { headers: { 'X-CSRF-Token': CSRF } });
        const data = await res.json();

        const fmtPeso = n => Math.round(n).toLocaleString('es-CO');

        const totalEl    = document.getElementById('cajaTotalDisplay');
        const ingrEl     = document.getElementById('cajaIngresosDisplay');
        const retEl      = document.getElementById('cajaRetirosDisplay');
        if (totalEl) totalEl.textContent = fmtPeso(data.total);
        if (ingrEl)  ingrEl.textContent  = `+$${fmtPeso(data.ingresosHoy)}`;
        if (retEl)   retEl.textContent   = `-$${fmtPeso(data.retirosHoy)}`;

        // También actualizar pendiente si cambió
        if (typeof data.ventasPendientes === 'number') {
            pendienteAcum = data.ventasPendientes;
            actualizarPanelLiquidacion();
        }

        renderCajaMovimientos(data.movimientos);
    } catch (e) {
        console.error('Error al refrescar caja:', e);
    }
}

async function submitAjusteCaja(event, accion) {
    event.preventDefault();
    const form    = event.target;
    const alerta  = document.getElementById('alertaCaja');
    const btn     = form.querySelector('button[type="submit"]');
    const valor   = parseFloat(form.querySelector('[name="valor"]').value);
    const concepto = form.querySelector('[name="concepto"]').value.trim();

    if (!valor || valor <= 0) return;
    btn.disabled = true;

    try {
        const res  = await fetch('/caja/ajuste', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body:    JSON.stringify({ accion, valor, concepto }),
        });
        const data = await res.json();

        if (data.status === 'ok') {
            form.reset();
            mostrarAlerta(alerta, `✅ ${accion === 'anadir' ? 'Añadido' : 'Retirado'} $${fmt(data.valor)} correctamente`, 'ok');
            await refrescarCaja();
        } else {
            mostrarAlerta(alerta, `⚠️ ${data.mensaje ?? 'Error'}`, 'err');
        }
    } catch {
        mostrarAlerta(alerta, '⚠️ Error de conexión', 'err');
    } finally {
        btn.disabled = false;
    }
    setTimeout(() => alerta.classList.add('hidden'), 4000);
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof CAJA_MOV_INICIAL !== 'undefined') {
        renderCajaMovimientos(CAJA_MOV_INICIAL);
    }
});

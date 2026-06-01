/* ─── Kokoro Pollo — Ventas POS ─────────────────────────── */
/* Requiere que la vista inyecte antes: PRODUCTOS, totalDiaAcum */

const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ── Estado global ── */
let prodSeleccionado = null;  // { id, nombre, precio, stock, cat }
let preparacionActual = null; // Asado | Broaster
let corteActual      = null;  // { nombre, mult }
let carrito          = [];    // [{ uid, id, nombre, corte, preparacion, cantForm, cantInv, precio, subtotal, costoUnit, costoSubtotal, margenSubtotal }]
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
    preparacionActual = null;
    corteActual = null;

    const esPolloCrudo = prodSeleccionado.cat === 'Pollo Crudo';
    const stockTxt = esPolloCrudo
        ? `Stock: ${Math.floor(prodSeleccionado.stock / 4)} pollos (${prodSeleccionado.stock} cuartos)`
        : `Stock: ${prodSeleccionado.stock} uds`;
    const precioTxt = esPolloCrudo
        ? `$${fmt(prodSeleccionado.precio * 4)} costo base/pollo`
        : `$${fmt(prodSeleccionado.precio)} / unidad`;

    document.getElementById('cfgNombre').textContent = prodSeleccionado.nombre;
    document.getElementById('cfgPrecio').textContent =
        precioTxt + ' · ' + stockTxt;
    document.getElementById('cfgCantidad').value = 1;
    document.getElementById('cfgSubtotal').textContent = '$0';
    document.getElementById('alertaStockCfg').classList.add('hidden');
    document.getElementById('btnAgregar').disabled = true;

    const esPollo   = esPolloCrudo;
    const secPrep   = document.getElementById('seccionPreparacion');
    const secCorte  = document.getElementById('seccionCorte');
    secPrep.classList.toggle('hidden', !esPollo);
    secCorte.classList.toggle('hidden', !esPollo);

    document.querySelectorAll('#seccionPreparacion .corte-btn').forEach(b => b.classList.remove('activo'));

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
    preparacionActual = null;
    corteActual      = null;
    document.querySelectorAll('.prod-card').forEach(c => c.classList.remove('activa'));
    document.querySelectorAll('.corte-btn').forEach(b => b.classList.remove('activo'));
    document.getElementById('configPanel').classList.add('hidden');
}

/* ── Seleccionar preparación ── */
function seleccionarPreparacion(btn, tipo) {
    if (!prodSeleccionado || prodSeleccionado.cat !== 'Pollo Crudo') return;
    document.querySelectorAll('#seccionPreparacion .corte-btn').forEach(b => b.classList.remove('activo'));
    btn.classList.add('activo');
    preparacionActual = tipo;
    corteActual = null;
    document.querySelectorAll('#seccionCorte .corte-btn').forEach(b => b.classList.remove('activo'));
    document.getElementById('cfgSubtotal').textContent = '$0';
    document.getElementById('btnAgregar').disabled = true;
}

/* ── Seleccionar corte ── */
function seleccionarCorte(btn, nombre) {
    if (!prodSeleccionado) return;
    if (prodSeleccionado.cat === 'Pollo Crudo' && !preparacionActual) return;

    document.querySelectorAll('#seccionCorte .corte-btn').forEach(b => b.classList.remove('activo'));
    btn.classList.add('activo');
    const mult     = parseInt(btn.dataset.mult);
    const corteKey = btn.dataset.corteKey ?? null;

    // Precio configurado para este corte (si existe y es > 0)
    let precioCorte = null;
    if (prodSeleccionado && corteKey && preparacionActual && PRECIOS_POLLO[preparacionActual]) {
        const p = PRECIOS_POLLO[preparacionActual][corteKey];
        if (p && p > 0) precioCorte = p;
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
    if (esPollo && !preparacionActual) return;

    // Con precio config: sub = precioCorte × cant
    // precio enviado al servidor = precioCorte / mult → total en server = (precioCorte/mult) × cantInv = precioCorte × cant ✓
    // Sin config: comportamiento original precio_por_cuarto × cantInv
    let sub, precio;
    if (corteActual.precioCorte) {
        sub    = corteActual.precioCorte * cant;
        precio = corteActual.precioCorte / corteActual.mult;
    } else {
        sub    = prodSeleccionado.precio * cantInv;
        precio = prodSeleccionado.precio;
    }

    const costoUnit = prodSeleccionado.precio * corteActual.mult;
    const costoSubtotal = costoUnit * cant;
    const margenSubtotal = sub - costoSubtotal;
    const nombreVenta = esPollo
        ? `${preparacionActual} - ${corteActual.nombre}`
        : prodSeleccionado.nombre;

    carrito.push({
        uid:      Math.random().toString(36).slice(2, 8),
        id:       prodSeleccionado.id,
        nombre:   nombreVenta,
        corte:    corteActual.nombre,
        preparacion: preparacionActual,
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
            preparacion: null,
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
                    ${item.preparacion ? item.preparacion + ' · ' : ''}
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

async function refrescarCaja() {
    try {
        const res  = await fetch('/caja/resumen', { headers: { 'X-CSRF-Token': CSRF } });
        const data = await res.json();

        const fmtPeso = n => Math.round(n).toLocaleString('es-CO');

        const totalEl    = document.getElementById('cajaTotalDisplay');
        const ingrEl     = document.getElementById('cajaIngresosDisplay');
        const retEl      = document.getElementById('cajaRetirosDisplay');
        const tbodyEl    = document.getElementById('cajaTbody');

        if (totalEl) totalEl.textContent = fmtPeso(data.total);
        if (ingrEl)  ingrEl.textContent  = `+$${fmtPeso(data.ingresosHoy)}`;
        if (retEl)   retEl.textContent   = `-$${fmtPeso(data.retirosHoy)}`;

        // También actualizar pendiente si cambió
        if (typeof data.ventasPendientes === 'number') {
            pendienteAcum = data.ventasPendientes;
            actualizarPanelLiquidacion();
        }

        if (tbodyEl && Array.isArray(data.movimientos)) {
            tbodyEl.innerHTML = data.movimientos.length === 0
                ? '<tr><td colspan="3" style="padding:1.5rem; text-align:center; color:#9ca3af;">Sin actividad hoy</td></tr>'
                : data.movimientos.map(m => {
                    const esVenta  = m.origen === 'ventas';
                    const esRetiro = m.tipo === 'retiro';
                    const color    = esRetiro ? '#fca5a5' : (esVenta ? 'var(--oro)' : '#4ade80');
                    const signo    = esRetiro ? '-' : '+';
                    const icono    = esVenta ? '🛒' : (esRetiro ? '▼' : '▲');
                    const liq      = esVenta && m.liquidado ? ' <span style="color:#6b7280;font-size:.7rem;">✓</span>' : '';
                    const hora     = new Date(m.fecha.replace(' ', 'T')).toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' });
                    const concepto = (m.concepto || '').replace(/</g, '&lt;');
                    const ordenId  = esVenta && m.orden_id ? ` <span style="color:#6b7280;font-size:.7rem;">#${m.orden_id}</span>` : '';
                    return `<tr class="border-b" style="border-color:var(--rojo-mid);">
                        <td class="px-3 py-2" style="color:${color}; font-weight:700;">${icono} ${concepto}${liq}${ordenId}</td>
                        <td class="px-3 py-2 text-right whitespace-nowrap font-black" style="color:${color};">${signo}$${fmtPeso(m.valor)}</td>
                        <td class="px-3 py-2 text-right text-xs" style="color:#9ca3af;">${hora}</td>
                    </tr>`;
                }).join('');
        }
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

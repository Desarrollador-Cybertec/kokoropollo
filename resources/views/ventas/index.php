<?php

declare(strict_types=1);

use App\Core\{Csrf, View};

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas — Kokoro Pollo</title>
    <?= Csrf::meta() ?>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="page-ventas">

<h1>Módulo de Ventas</h1>
<button class="volver-btn-claro"
        onclick="window.location.href='<?= View::escape($dashboardUrl) ?>'">← REGRESAR</button>

<div class="form-venta">
    <select id="producto" required>
        <option value="">Seleccione producto</option>
        <?php foreach ($productos as $p): ?>
            <option value="<?= (int) $p['id'] ?>"
                    data-precio="<?= (float) $p['valor'] ?>">
                <?= View::escape($p['articulo']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="text"   id="precio"   placeholder="Precio" readonly>
    <input type="number" id="cantidad" min="1" value="1">
    <div class="total-label">Total: $<span id="total">0</span></div>
    <button class="btn-venta"      id="registrarVenta">Registrar Venta</button>
    <button class="btn-venta azul" id="generarFactura">Generar Factura</button>
</div>

<!-- Tabla de ventas -->
<table class="ventas-table" id="tablaVentas" style="display:none;">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Precio Unitario</th>
            <th>Cantidad</th>
            <th>Total</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<div id="totalVentasDia" class="total-ventas-dia" style="display:none;">
    Total Ventas del Día: $<?= number_format((float) $totalDia, 0, ',', '.') ?>
</div>

<!-- Modal Factura -->
<div id="modalFactura" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Factura</h2>
            <button class="modal-close" id="cerrarModal">&times;</button>
        </div>
        <div id="contenidoFactura"></div>
        <br>
        <button class="btn-venta" onclick="imprimirFactura()">Imprimir Factura</button>
    </div>
</div>

<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

const productoSelect  = document.getElementById('producto');
const precioInput     = document.getElementById('precio');
const cantidadInput   = document.getElementById('cantidad');
const totalSpan       = document.getElementById('total');
const registrarBtn    = document.getElementById('registrarVenta');
const generarBtn      = document.getElementById('generarFactura');
const tablaVentas     = document.getElementById('tablaVentas');
const tbody           = tablaVentas.querySelector('tbody');
const totalDiaDiv     = document.getElementById('totalVentasDia');
const modalFactura    = document.getElementById('modalFactura');
const contenidoFact   = document.getElementById('contenidoFactura');

let totalDia = <?= (float) $totalDia ?>;

function fmt(n) { return n.toLocaleString('es-CO'); }

function actualizarTotal() {
    const precio   = parseFloat(precioInput.getAttribute('data-valor')) || 0;
    const cantidad = parseInt(cantidadInput.value) || 0;
    totalSpan.textContent = fmt(precio * cantidad);
}

productoSelect.addEventListener('change', function() {
    const opt   = this.options[this.selectedIndex];
    const precio = parseFloat(opt.getAttribute('data-precio')) || 0;
    precioInput.value = `$${fmt(precio)}`;
    precioInput.setAttribute('data-valor', precio);
    actualizarTotal();
});

cantidadInput.addEventListener('input', actualizarTotal);

registrarBtn.addEventListener('click', async function() {
    const productoId  = productoSelect.value;
    const productoNom = productoSelect.options[productoSelect.selectedIndex]?.text || '';
    const precio      = parseFloat(precioInput.getAttribute('data-valor')) || 0;
    const cantidad    = parseInt(cantidadInput.value) || 0;
    const total       = precio * cantidad;

    if (!productoId || cantidad <= 0) {
        alert('Seleccione un producto y cantidad válida');
        return;
    }

    try {
        const res = await fetch('/ventas/store', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN,
            },
            body: JSON.stringify({
                inventario_id:   parseInt(productoId),
                cantidad:        cantidad,
                precio_unitario: precio,
            }),
        });

        const data = await res.json();
        if (data.status !== 'ok') {
            alert('Error al registrar: ' + (data.mensaje ?? 'desconocido'));
            return;
        }
    } catch (e) {
        alert('Error de conexión al registrar la venta.');
        return;
    }

    tablaVentas.style.display = 'table';
    const fecha = new Date().toLocaleString('es-CO');
    tbody.insertAdjacentHTML('beforeend', `<tr>
        <td>${productoNom}</td>
        <td>$${fmt(precio)}</td>
        <td>${cantidad}</td>
        <td>$${fmt(total)}</td>
        <td>${fecha}</td>
    </tr>`);

    totalDia += total;
    totalDiaDiv.style.display = 'block';
    totalDiaDiv.textContent   = `Total Ventas del Día: $${fmt(totalDia)}`;

    productoSelect.value = '';
    precioInput.value = '';
    precioInput.removeAttribute('data-valor');
    cantidadInput.value = 1;
    totalSpan.textContent = '0';
});

generarBtn.addEventListener('click', function() {
    if (tbody.children.length === 0) {
        alert('No hay productos para generar la factura');
        return;
    }
    contenidoFact.innerHTML = tablaVentas.outerHTML;
    modalFactura.classList.add('open');
});

document.getElementById('cerrarModal').addEventListener('click', () => {
    modalFactura.classList.remove('open');
});

modalFactura.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
});

function imprimirFactura() {
    const w = window.open('', '', 'width=800,height=600');
    w.document.write('<html><head><title>Factura Kokoro Pollo</title></head><body>');
    w.document.write(contenidoFact.innerHTML);
    w.document.write('</body></html>');
    w.document.close();
    w.print();
}
</script>

</body>
</html>

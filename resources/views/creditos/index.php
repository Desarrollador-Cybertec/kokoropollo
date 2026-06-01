<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

$creditos     = $creditos     ?? [];
$resumen      = $resumen      ?? [];
$empleados    = $empleados    ?? [];
$dashboardUrl = $dashboardUrl ?? '/dashboard';
$pageTitle    = $pageTitle    ?? 'Créditos Empleados — Kokoro Pollo';

$p = fn(float $v) => '$' . number_format($v, 0, ',', '.');

require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-20" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-5xl mx-auto px-4">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
        <h1 class="text-3xl font-black tracking-wide" style="color:var(--oro);">💳 Créditos a Empleados</h1>
        <?php if (!empty($empleados)): ?>
        <button onclick="abrirModal()"
                class="font-black text-lg px-6 py-3 rounded-xl btn-primary">
            ➕ Nuevo Crédito
        </button>
        <?php endif; ?>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl px-5 py-4 text-center" style="background-color:var(--rojo-card); border:1px solid var(--rojo-mid);">
            <p class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#9ca3af;">Pendientes</p>
            <p class="text-3xl font-black" style="color:var(--oro);"><?= (int)($resumen['total_pendientes'] ?? 0) ?></p>
        </div>
        <div class="rounded-2xl px-5 py-4 text-center" style="background-color:#4a0e0e; border:1px solid #b91c1c;">
            <p class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#fca5a5;">Vencidos</p>
            <p class="text-3xl font-black" style="color:#fca5a5;"><?= (int)($resumen['total_vencidos'] ?? 0) ?></p>
        </div>
        <div class="rounded-2xl px-5 py-4 text-center" style="background-color:#3a2a0f; border:1px solid var(--oro);">
            <p class="text-xs font-bold uppercase tracking-wider mb-1" style="color:var(--oro);">Cartera total</p>
            <p class="text-2xl font-black" style="color:var(--oro);"><?= $p((float)($resumen['cartera_total'] ?? 0)) ?></p>
        </div>
    </div>

    <!-- Tabla -->
    <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color:var(--rojo-card);">
        <div class="overflow-x-auto">
            <table class="w-full text-base" id="tablaCreditos">
                <thead>
                    <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                        <th class="px-4 py-3 text-left font-bold">Empleado</th>
                        <th class="px-4 py-3 text-left font-bold">Valor</th>
                        <th class="px-4 py-3 text-left font-bold">Préstamo</th>
                        <th class="px-4 py-3 text-left font-bold">Compromiso pago</th>
                        <th class="px-4 py-3 text-left font-bold">Estado</th>
                        <th class="px-4 py-3 text-left font-bold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($creditos)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-10 text-lg" style="color:#9ca3af;">
                            Sin créditos registrados
                        </td>
                    </tr>
                <?php else: foreach ($creditos as $c):
                    $estado  = $c['estado'];
                    $esPend  = $estado === 'pendiente';
                    $esVenc  = $estado === 'vencido';
                    $esPago  = $estado === 'pagado';
                    $badgeStyle = match($estado) {
                        'pendiente' => 'background-color:#3a2a0f; color:var(--oro);',
                        'vencido'   => 'background-color:#4a0e0e; color:#fca5a5;',
                        'pagado'    => 'background-color:#132a1e; color:#4ade80;',
                    };
                    $badgeLabel = match($estado) {
                        'pendiente' => '⏳ Pendiente',
                        'vencido'   => '🔴 Vencido',
                        'pagado'    => '✅ Pagado',
                    };
                ?>
                    <tr class="border-b text-white tr-dark" style="border-color:var(--rojo-mid);">
                        <td class="px-4 py-3">
                            <p class="font-semibold"><?= View::escape($c['nombre_empleado']) ?></p>
                            <p class="text-xs" style="color:#9ca3af;"><?= View::escape($c['usuario_empleado']) ?></p>
                        </td>
                        <td class="px-4 py-3 font-black" style="color:var(--oro);">
                            <?= $p((float)$c['valor']) ?>
                        </td>
                        <td class="px-4 py-3 text-sm" style="color:#d1d5db;">
                            <?= date('d/m/Y', strtotime($c['fecha_prestamo'])) ?>
                        </td>
                        <td class="px-4 py-3 text-sm <?= $esVenc ? 'font-bold' : '' ?>"
                            style="color:<?= $esVenc ? '#fca5a5' : '#d1d5db' ?>;">
                            <?= date('d/m/Y', strtotime($c['fecha_compromiso_pago'])) ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 rounded-full text-xs font-bold" style="<?= $badgeStyle ?>">
                                <?= $badgeLabel ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2 flex-wrap">
                                <?php if ($esPend || $esVenc): ?>
                                <button onclick="pagarCredito(<?= (int)$c['id'] ?>, '<?= addslashes($c['nombre_empleado']) ?>', '<?= $p((float)$c['valor']) ?>')"
                                        class="font-bold px-3 py-2 rounded-lg text-sm btn-green">
                                    💵 Cobrar
                                </button>
                                <?php endif; ?>
                                <?php if ($esPend): ?>
                                <button onclick="vencerCredito(<?= (int)$c['id'] ?>)"
                                        class="font-bold px-3 py-2 rounded-lg text-sm btn-danger">
                                    ⚠️ Vencer
                                </button>
                                <?php endif; ?>
                                <?php if ($esPago): ?>
                                <span class="text-xs" style="color:#9ca3af;">
                                    Pagado <?= $c['fecha_pago'] ? date('d/m/Y', strtotime($c['fecha_pago'])) : '' ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Nuevo Crédito -->
<div id="modal" class="hidden fixed inset-0 flex items-center justify-center z-50 p-4"
     style="background-color:rgba(0,0,0,.65);">
    <div class="rounded-2xl shadow-2xl w-full max-w-md p-8" style="background-color:var(--rojo-card);">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-black" style="color:var(--oro);">Nuevo Crédito</h2>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-red-400 text-4xl font-black leading-none">&times;</button>
        </div>

        <div class="space-y-4">
            <div>
                <label class="text-sm font-bold block mb-1" style="color:var(--oro);">Empleado</label>
                <select id="empleadoId" class="w-full text-lg px-4 py-3 rounded-xl input-dark" style="appearance:none; color:var(--oro);">
                    <option value="" style="background-color:var(--rojo-deep);">Seleccione...</option>
                    <?php foreach ($empleados as $e): ?>
                    <option value="<?= (int)$e['id'] ?>" style="background-color:var(--rojo-deep); color:var(--oro);">
                        <?= View::escape($e['nombre']) ?> (<?= View::escape($e['usuario']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-sm font-bold block mb-1" style="color:var(--oro);">Valor del préstamo</label>
                <input type="number" id="valorCredito" min="1" step="1000" placeholder="Ej: 50000"
                       class="w-full text-xl px-4 py-3 rounded-xl input-dark">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-bold block mb-1" style="color:var(--oro);">Fecha préstamo</label>
                    <input type="date" id="fechaPrestamo" value="<?= date('Y-m-d') ?>"
                           class="w-full text-lg px-4 py-3 rounded-xl input-dark">
                </div>
                <div>
                    <label class="text-sm font-bold block mb-1" style="color:var(--oro);">Compromiso pago</label>
                    <input type="date" id="fechaCompromiso"
                           class="w-full text-lg px-4 py-3 rounded-xl input-dark">
                </div>
            </div>
            <div>
                <label class="text-sm font-bold block mb-1" style="color:var(--oro);">Observaciones (opcional)</label>
                <textarea id="obsCredito" rows="2" maxlength="500"
                          class="w-full text-base px-4 py-3 rounded-xl input-dark resize-none"
                          placeholder="Ej: Para cubrir gastos de salud..."></textarea>
            </div>
        </div>

        <div id="alertaModal" class="hidden mt-4 px-4 py-3 rounded-xl text-sm font-bold"></div>

        <div class="flex gap-4 mt-6">
            <button onclick="cerrarModal()"
                    class="flex-1 font-bold text-lg py-4 rounded-xl btn-secondary">Cancelar</button>
            <button id="btnGuardar" onclick="guardarCredito()"
                    class="flex-1 font-black text-lg py-4 rounded-xl btn-primary">Registrar</button>
        </div>
    </div>
</div>

<a href="<?= View::escape($dashboardUrl) ?>"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg btn-primary">
    ← Panel
</a>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function abrirModal() { document.getElementById('modal').classList.remove('hidden'); }
function cerrarModal() { document.getElementById('modal').classList.add('hidden'); }
document.getElementById('modal').addEventListener('click', e => { if (e.target === document.getElementById('modal')) cerrarModal(); });

async function guardarCredito() {
    const alerta     = document.getElementById('alertaModal');
    const empleadoId = parseInt(document.getElementById('empleadoId').value);
    const valor      = parseFloat(document.getElementById('valorCredito').value);
    const fechaPrest = document.getElementById('fechaPrestamo').value;
    const fechaComp  = document.getElementById('fechaCompromiso').value;
    const obs        = document.getElementById('obsCredito').value.trim();

    if (!empleadoId || !valor || valor <= 0 || !fechaComp) {
        mostrarAlerta(alerta, '⚠️ Completa todos los campos obligatorios.', 'err');
        return;
    }

    document.getElementById('btnGuardar').disabled = true;

    try {
        const res  = await fetch('/creditos/crear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify({
                empleado_id: empleadoId, valor,
                fecha_prestamo: fechaPrest, fecha_compromiso: fechaComp,
                observaciones: obs,
            }),
        });
        const data = await res.json();
        if (data.status === 'ok') {
            cerrarModal();
            location.reload();
        } else {
            mostrarAlerta(alerta, '❌ ' + (data.mensaje ?? 'Error al registrar.'), 'err');
        }
    } catch {
        mostrarAlerta(alerta, '❌ Error de conexión.', 'err');
    } finally {
        document.getElementById('btnGuardar').disabled = false;
    }
}

async function pagarCredito(id, nombre, valor) {
    if (!confirm(`¿Registrar pago de ${valor} de ${nombre}?`)) return;
    const res  = await fetch('/creditos/pagar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify({ id }),
    });
    const data = await res.json();
    if (data.status === 'ok') location.reload();
    else alert(data.mensaje ?? 'Error al registrar pago.');
}

async function vencerCredito(id) {
    if (!confirm('¿Marcar este crédito como vencido?')) return;
    const res  = await fetch('/creditos/vencer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify({ id }),
    });
    const data = await res.json();
    if (data.status === 'ok') location.reload();
}

function mostrarAlerta(el, texto, tipo) {
    el.textContent = texto;
    el.style.backgroundColor = tipo === 'ok' ? '#132a1e' : '#4a0e0e';
    el.style.color           = tipo === 'ok' ? '#4ade80' : '#fca5a5';
    el.classList.remove('hidden');
}
</script>

</body>
</html>

<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

$total = isset($total) ? (float) $total : 0.0;
$ingresosHoy = isset($ingresosHoy) ? (float) $ingresosHoy : 0.0;
$retirosHoy = isset($retirosHoy) ? (float) $retirosHoy : 0.0;
$hoy = isset($hoy) ? (string) $hoy : date('Y-m-d');
$ayer = isset($ayer) ? (string) $ayer : date('Y-m-d', strtotime('-1 day'));
$lunEs = isset($lunEs) ? (string) $lunEs : date('Y-m-d', strtotime('monday this week'));
$priMes = isset($priMes) ? (string) $priMes : date('Y-m-01');
$dashboardUrl = isset($dashboardUrl) ? (string) $dashboardUrl : '/dashboard';
$movimientosHoy      = (isset($movimientosHoy) && is_array($movimientosHoy)) ? $movimientosHoy : [];
$ventasPendientesHoy = isset($ventasPendientesHoy) ? (float) $ventasPendientesHoy : 0.0;
$aperturaHoy = isset($aperturaHoy) && is_array($aperturaHoy) ? $aperturaHoy : null;

$pageTitle = 'Caja — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-28" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-3xl mx-auto px-4">

    <h1 class="text-4xl font-black text-center tracking-wide mb-6" style="color:var(--oro);">
        💰 TOTAL EN CAJA
    </h1>

    <!-- Total en caja -->
    <div class="bg-white text-6xl font-black text-center py-8 rounded-2xl shadow-2xl mb-4 tracking-wider"
         style="color:var(--rojo-dark);">
        $<?= number_format((float) $total, 0, ',', '.') ?>
    </div>

    <!-- Mini-resumen del día -->
    <div class="grid grid-cols-2 gap-3 mb-3">
        <div class="rounded-xl px-4 py-3 text-center" style="background-color:#134e2a;">
            <div class="text-xs font-bold uppercase tracking-wider text-green-300 mb-1">Ingresos hoy</div>
            <div class="text-2xl font-black text-green-300">
                +$<?= number_format($ingresosHoy, 0, ',', '.') ?>
            </div>
        </div>
        <div class="rounded-xl px-4 py-3 text-center" style="background-color:#4a0e0e;">
            <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#fca5a5;">Retiros hoy</div>
            <div class="text-2xl font-black" style="color:#fca5a5;">
                -$<?= number_format($retirosHoy, 0, ',', '.') ?>
            </div>
        </div>
    </div>

    <!-- Banner apertura de caja (solo admin) -->
    <?php if ($esAdmin ?? false): ?>
    <?php if ($aperturaHoy): ?>
    <div class="rounded-xl px-5 py-3 mb-3 flex items-center justify-between gap-4 flex-wrap"
         style="background-color:#132a1e; border:1px solid #1d6b3a;">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider" style="color:#4ade80;">🔓 Caja abierta</span>
            <span class="ml-2 text-sm font-semibold" style="color:#9ca3af;">
                Base: <strong style="color:#4ade80;">$<?= number_format((float)$aperturaHoy['base_inicial'], 0, ',', '.') ?></strong>
                · <?= View::escape($aperturaHoy['nombre_usuario']) ?>
                · <?= date('H:i', strtotime($aperturaHoy['created_at'])) ?>
            </span>
        </div>
        <a href="/caja/apertura" class="text-xs font-bold px-3 py-1 rounded-lg btn-secondary">
            Ver detalle
        </a>
    </div>
    <?php else: ?>
    <div class="rounded-xl px-5 py-3 mb-3 flex items-center justify-between gap-4 flex-wrap"
         style="background-color:#3a1a08; border:2px solid #d97706;">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider" style="color:#fbbf24;">⚠️ Caja sin apertura</span>
            <span class="ml-2 text-sm" style="color:#9ca3af;">No se ha registrado la apertura del día</span>
        </div>
        <a href="/caja/apertura" class="font-bold text-sm px-4 py-2 rounded-xl btn-primary">
            Abrir Caja →
        </a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Ventas pendientes de liquidar -->
    <?php if ($ventasPendientesHoy > 0): ?>
    <div class="rounded-xl px-5 py-4 mb-6 flex items-center justify-between gap-4 flex-wrap"
         style="background-color:#3a2a0f; border:2px solid var(--oro);">
        <div>
            <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:var(--oro);">
                🛒 Ventas sin liquidar
            </div>
            <div class="text-2xl font-black" style="color:var(--oro);">
                $<?= number_format($ventasPendientesHoy, 0, ',', '.') ?>
            </div>
        </div>
        <a href="/ventas" class="font-bold text-sm px-5 py-2 rounded-xl btn-primary">
            Ir a Ventas →
        </a>
    </div>
    <?php else: ?>
    <div class="mb-6"></div>
    <?php endif; ?>

    <!-- ALSÉS — Retiro de Seguridad (solo admin) -->
    <?php if ($esAdmin ?? false): ?>
    <div class="rounded-2xl shadow-xl p-5 mb-4" style="background-color:var(--rojo-card); border:1px solid #78350f;">
        <h3 class="font-black text-base uppercase tracking-wider mb-3" style="color:#fbbf24;">
            🔒 ALSÉ — Retiro de Seguridad
        </h3>
        <p class="text-xs mb-3" style="color:#9ca3af;">Traslado de efectivo por seguridad. No es un gasto operativo.</p>
        <form id="formAlse" class="flex flex-wrap gap-3">
            <input type="number" step="1" min="1" placeholder="Valor" name="valor"
                   class="input-dark px-4 py-3 rounded-xl text-lg font-bold flex-1 min-w-[120px]">
            <input type="text" placeholder="Motivo" name="motivo" maxlength="255"
                   class="input-dark px-4 py-3 rounded-xl text-lg flex-[2] min-w-[180px]">
            <button type="button" onclick="registrarAlse()"
                    class="font-black text-base px-6 py-3 rounded-xl whitespace-nowrap"
                    style="background-color:#78350f; color:#fbbf24; transition:background-color .15s;"
                    onmouseover="this.style.backgroundColor='#92400e'"
                    onmouseout="this.style.backgroundColor='#78350f'">
                🔒 ALSÉ
            </button>
        </form>
        <div id="alertaAlse" class="hidden mt-2 text-sm font-bold px-4 py-2 rounded-xl"></div>
    </div>
    <?php endif; ?>

    <!-- Filtros rápidos -->
    <div class="rounded-2xl shadow-xl p-4 mb-6" style="background-color:var(--rojo-card);">
        <p class="text-sm font-bold uppercase tracking-wider mb-3" style="color:var(--oro);">
            📅 Ver historial por período
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="/historial?desde=<?= $hoy ?>&hasta=<?= $hoy ?>"
               class="font-bold px-5 py-2 rounded-xl text-sm btn-primary">
                Hoy
            </a>
            <a href="/historial?desde=<?= $ayer ?>&hasta=<?= $ayer ?>"
               class="font-bold px-5 py-2 rounded-xl text-sm btn-secondary">
                Ayer
            </a>
            <a href="/historial?desde=<?= $lunEs ?>&hasta=<?= $hoy ?>"
               class="font-bold px-5 py-2 rounded-xl text-sm btn-secondary">
                Esta semana
            </a>
            <a href="/historial?desde=<?= $priMes ?>&hasta=<?= $hoy ?>"
               class="font-bold px-5 py-2 rounded-xl text-sm btn-secondary">
                Este mes
            </a>
            <a href="/historial"
               class="font-bold px-5 py-2 rounded-xl text-sm btn-secondary">
                📋 Todo el historial
            </a>
        </div>
    </div>

    <!-- Acciones -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">

        <!-- Añadir -->
        <div class="rounded-2xl p-6 shadow-xl" style="background-color:var(--rojo-card);">
            <h2 class="text-xl font-black tracking-wide mb-5 text-center uppercase text-green-400">
                ➕ Añadir Dinero
            </h2>
            <form method="POST" action="/caja" class="flex flex-col gap-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="accion" value="anadir">
                <input type="number" step="1" name="valor"
                       placeholder="Ej: 50000" required min="1"
                       class="input-green text-xl font-bold px-4 py-4 rounded-xl w-full">
                <input type="text" name="concepto"
                       placeholder="Ej: Venta de la tarde" required maxlength="255"
                       class="input-green text-lg px-4 py-4 rounded-xl w-full">
                <button type="submit"
                        class="font-black text-xl py-4 rounded-xl uppercase tracking-wide btn-green">
                    ✅ AÑADIR
                </button>
            </form>
        </div>

        <!-- Retirar -->
        <div class="rounded-2xl p-6 shadow-xl" style="background-color:var(--rojo-card);">
            <h2 class="text-xl font-black tracking-wide mb-5 text-center uppercase" style="color:#fca5a5;">
                ➖ Retirar Dinero
            </h2>
            <form method="POST" action="/caja" class="flex flex-col gap-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="accion" value="retirar">
                <input type="number" step="1" name="valor"
                       placeholder="Ej: 20000" required min="1"
                       class="input-red text-xl font-bold px-4 py-4 rounded-xl w-full">
                <input type="text" name="concepto"
                       placeholder="Ej: Compra de insumos" required maxlength="255"
                       class="input-red text-lg px-4 py-4 rounded-xl w-full">
                <button type="submit"
                        class="font-black text-xl py-4 rounded-xl uppercase tracking-wide btn-danger">
                    💸 RETIRAR
                </button>
            </form>
        </div>

    </div>

    <!-- Movimientos de hoy (unificado: ventas + caja) -->
    <?php if (!empty($movimientosHoy)): ?>
    <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color:var(--rojo-card);">
        <div class="px-5 py-4" style="background-color:var(--rojo-mid);">
            <h3 class="font-black text-lg uppercase tracking-wider" style="color:var(--oro);">
                🕐 Actividad de hoy
            </h3>
        </div>
        <div class="overflow-x-auto" style="max-height:360px; overflow-y:auto;">
            <table class="w-full text-base">
                <thead class="sticky top-0" style="background-color:var(--rojo-mid);">
                    <tr style="color:var(--oro);">
                        <th class="px-4 py-3 text-left font-bold">Tipo</th>
                        <th class="px-4 py-3 text-left font-bold">Detalle</th>
                        <th class="px-4 py-3 text-left font-bold">Valor</th>
                        <th class="px-4 py-3 text-left font-bold">Hora</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($movimientosHoy as $m): ?>
                    <?php
                        $esVenta    = $m['origen'] === 'ventas';
                        $esIngreso  = $m['tipo'] === 'ingreso';
                        $esRetiro   = $m['tipo'] === 'retiro';
                        $liquidado  = $esVenta && ($m['liquidado'] ?? 0);
                    ?>
                    <tr class="border-b text-white tr-dark" style="border-color:var(--rojo-mid);">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <?php if ($esVenta): ?>
                                <span class="font-bold" style="color:var(--oro);">🛒 Venta</span>
                                <?php if ($liquidado): ?>
                                    <span class="text-xs ml-1" style="color:#9ca3af;">✓</span>
                                <?php endif; ?>
                            <?php elseif ($esIngreso): ?>
                                <span class="font-bold text-green-400">▲ Ingreso</span>
                            <?php else: ?>
                                <span class="font-bold" style="color:#fca5a5;">▼ Retiro</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3" style="color:#d1d5db;">
                            <?= View::escape($m['concepto'] ?: '—') ?>
                            <?php if ($esVenta && !empty($m['orden_id'])): ?>
                                <span class="text-xs ml-1" style="color:#6b7280;">#<?= View::escape($m['orden_id']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 font-bold"
                            style="color:<?= $esRetiro ? '#fca5a5' : ($esVenta ? 'var(--oro)' : '#4ade80') ?>;">
                            <?= $esRetiro ? '-' : '+' ?>$<?= number_format((float) $m['valor'], 0, ',', '.') ?>
                        </td>
                        <td class="px-4 py-3 text-sm" style="color:#9ca3af;">
                            <?= date('H:i', strtotime($m['fecha'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="rounded-2xl p-6 text-center" style="background-color:var(--rojo-card);">
        <p class="text-lg" style="color:#9ca3af;">Sin actividad registrada hoy</p>
    </div>
    <?php endif; ?>

</div>

<script>
const CSRF_CAJA = document.querySelector('meta[name="csrf-token"]').content;

async function registrarAlse() {
    const form   = document.getElementById('formAlse');
    const alerta = document.getElementById('alertaAlse');
    const valor  = parseFloat(form.querySelector('[name="valor"]').value);
    const motivo = form.querySelector('[name="motivo"]').value.trim();

    if (!valor || valor <= 0 || !motivo) {
        mostrarAlertaCaja(alerta, '⚠️ Ingresa valor y motivo.', 'err');
        return;
    }

    try {
        const res  = await fetch('/caja/alse', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_CAJA },
            body: JSON.stringify({ valor, motivo }),
        });
        const data = await res.json();
        if (data.status === 'ok') {
            form.reset();
            mostrarAlertaCaja(alerta, '✅ ALSÉ registrado correctamente.', 'ok');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarAlertaCaja(alerta, '❌ ' + (data.mensaje ?? 'Error'), 'err');
        }
    } catch {
        mostrarAlertaCaja(alerta, '❌ Error de conexión.', 'err');
    }
}

function mostrarAlertaCaja(el, texto, tipo) {
    el.textContent = texto;
    el.style.backgroundColor = tipo === 'ok' ? '#132a1e' : '#4a0e0e';
    el.style.color           = tipo === 'ok' ? '#4ade80' : '#fca5a5';
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 4000);
}
</script>

<!-- Botón regresar -->
<a href="<?= View::escape($dashboardUrl) ?>"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg btn-primary">
    ← REGRESAR
</a>

</body>
</html>

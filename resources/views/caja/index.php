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
$movimientosHoy = (isset($movimientosHoy) && is_array($movimientosHoy)) ? $movimientosHoy : [];

$pageTitle = 'Caja — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<body style="background-color:var(--rojo-deep);" class="min-h-screen py-8 pb-28">

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
    <div class="grid grid-cols-2 gap-3 mb-6">
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

    <!-- Movimientos de hoy -->
    <?php if (!empty($movimientosHoy)): ?>
    <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color:var(--rojo-card);">
        <div class="px-5 py-4" style="background-color:var(--rojo-mid);">
            <h3 class="font-black text-lg uppercase tracking-wider" style="color:var(--oro);">
                🕐 Movimientos de hoy
            </h3>
        </div>
        <div class="overflow-x-auto" style="max-height:280px; overflow-y:auto;">
            <table class="w-full text-base">
                <thead class="sticky top-0" style="background-color:var(--rojo-mid);">
                    <tr style="color:var(--oro);">
                        <th class="px-4 py-3 text-left font-bold">Tipo</th>
                        <th class="px-4 py-3 text-left font-bold">Concepto</th>
                        <th class="px-4 py-3 text-left font-bold">Valor</th>
                        <th class="px-4 py-3 text-left font-bold">Hora</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (array_reverse($movimientosHoy) as $m): ?>
                    <tr class="border-b text-white tr-dark" style="border-color:var(--rojo-mid);">
                        <td class="px-4 py-3">
                            <?php if ($m['tipo'] === 'ingreso'): ?>
                                <span class="font-bold text-green-400">▲ Ingreso</span>
                            <?php else: ?>
                                <span class="font-bold" style="color:#fca5a5;">▼ Retiro</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3" style="color:#d1d5db;">
                            <?= View::escape($m['concepto'] ?: '—') ?>
                        </td>
                        <td class="px-4 py-3 font-bold <?= $m['tipo'] === 'ingreso' ? 'text-green-400' : '' ?>"
                            <?= $m['tipo'] === 'retiro' ? 'style="color:#fca5a5;"' : '' ?>>
                            <?= $m['tipo'] === 'ingreso' ? '+' : '-' ?>$<?= number_format((float) $m['valor'], 0, ',', '.') ?>
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
        <p class="text-lg" style="color:#9ca3af;">Sin movimientos registrados hoy</p>
    </div>
    <?php endif; ?>

</div>

<!-- Botón regresar -->
<a href="<?= View::escape($dashboardUrl) ?>"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg btn-primary">
    ← REGRESAR
</a>

</body>
</html>

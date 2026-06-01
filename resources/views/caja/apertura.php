<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

$apertura       = $apertura ?? null;
$denominaciones = $denominaciones ?? [];
$dashboardUrl   = $dashboardUrl ?? '/dashboard';
$pageTitle      = $pageTitle ?? 'Apertura de Caja — Kokoro Pollo';

require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-20" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-2xl mx-auto px-4">

    <h1 class="text-4xl font-black text-center mb-2 tracking-wide" style="color:var(--oro);">
        🔓 Apertura de Caja
    </h1>
    <p class="text-center text-sm mb-6" style="color:#9ca3af;">
        <?= date('l d \d\e F \d\e Y') ?>
    </p>

    <?php if ($apertura): ?>
    <!-- ── Ya existe apertura hoy ── -->
    <div class="rounded-2xl shadow-xl p-6 mb-6 text-center" style="background-color:var(--rojo-card); border:2px solid var(--oro);">
        <div class="text-4xl mb-3">✅</div>
        <h2 class="text-2xl font-black mb-1" style="color:var(--oro);">Caja abierta</h2>
        <p class="text-base mb-1" style="color:#d1d5db;">
            Por <strong><?= View::escape($apertura['nombre_usuario']) ?></strong>
            a las <?= date('H:i', strtotime($apertura['created_at'])) ?>
        </p>
        <p class="text-3xl font-black mt-4" style="color:var(--oro);">
            Base: $<?= number_format((float) $apertura['base_inicial'], 0, ',', '.') ?>
        </p>

        <?php if (!empty($apertura['denominaciones'])): ?>
        <div class="mt-5 overflow-hidden rounded-xl" style="border:1px solid var(--rojo-mid);">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                        <th class="px-4 py-2 text-left font-bold">Denominación</th>
                        <th class="px-4 py-2 text-right font-bold">Cantidad</th>
                        <th class="px-4 py-2 text-right font-bold">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($apertura['denominaciones'] as $d):
                    if ((int)$d['cantidad'] === 0) continue; ?>
                    <tr class="border-b tr-dark text-white" style="border-color:var(--rojo-mid);">
                        <td class="px-4 py-2">$<?= number_format((int)$d['denominacion'], 0, ',', '.') ?></td>
                        <td class="px-4 py-2 text-right"><?= (int)$d['cantidad'] ?></td>
                        <td class="px-4 py-2 text-right font-semibold" style="color:var(--oro);">
                            $<?= number_format((float)$d['subtotal'], 0, ',', '.') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if (!empty($apertura['observaciones'])): ?>
        <p class="mt-4 text-sm italic" style="color:#9ca3af;">
            "<?= View::escape($apertura['observaciones']) ?>"
        </p>
        <?php endif; ?>
    </div>

    <div class="text-center">
        <a href="/caja" class="font-black text-lg px-8 py-4 rounded-xl btn-primary inline-block">
            ← Ir a Caja
        </a>
    </div>

    <?php else: ?>
    <!-- ── Formulario de apertura ── -->
    <form method="POST" action="/caja/apertura" id="formApertura">
        <?= Csrf::field() ?>

        <!-- Tabla de denominaciones -->
        <div class="rounded-2xl shadow-xl overflow-hidden mb-5" style="background-color:var(--rojo-card);">
            <div class="px-5 py-4" style="background-color:var(--rojo-mid);">
                <h2 class="font-black text-lg uppercase tracking-wider" style="color:var(--oro);">
                    💵 Conteo de base inicial
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-base">
                    <thead>
                        <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                            <th class="px-5 py-3 text-left font-bold">Denominación</th>
                            <th class="px-5 py-3 text-center font-bold">Cantidad</th>
                            <th class="px-5 py-3 text-right font-bold">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($denominaciones as $valor): ?>
                        <tr class="border-b tr-dark text-white" style="border-color:var(--rojo-mid);"
                            data-denominacion="<?= $valor ?>">
                            <td class="px-5 py-3 font-semibold">
                                $<?= number_format($valor, 0, ',', '.') ?>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <input type="number" name="den_<?= $valor ?>"
                                       min="0" value="0"
                                       class="input-dark text-center font-bold text-lg rounded-xl px-3 py-2 w-24"
                                       onchange="recalcDen(this)"
                                       oninput="recalcDen(this)">
                            </td>
                            <td class="px-5 py-3 text-right font-black subtotal-col" style="color:var(--oro);">
                                $0
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color:var(--rojo-mid);">
                            <td class="px-5 py-4 font-black text-white" colspan="2">TOTAL BASE INICIAL</td>
                            <td class="px-5 py-4 text-right font-black text-2xl" style="color:var(--oro);" id="totalBase">
                                $0
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Observaciones -->
        <div class="rounded-2xl shadow-xl p-5 mb-5" style="background-color:var(--rojo-card);">
            <label class="block text-sm font-bold mb-2" style="color:var(--oro);">
                Observaciones (opcional)
            </label>
            <textarea name="observaciones" rows="2" maxlength="500"
                      placeholder="Ej: Base incompleta, faltaban $5.000..."
                      class="input-dark w-full px-4 py-3 rounded-xl text-base resize-none"></textarea>
        </div>

        <button type="submit"
                class="w-full font-black text-xl py-5 rounded-2xl shadow-xl uppercase tracking-wide btn-primary">
            🔓 Registrar Apertura
        </button>
    </form>
    <?php endif; ?>

</div>

<!-- Botón regresar -->
<a href="<?= View::escape($dashboardUrl) ?>"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg btn-secondary">
    ← Panel
</a>

<script>
function fmt(n) {
    return Math.round(n).toLocaleString('es-CO');
}

function recalcDen(input) {
    const row  = input.closest('tr[data-denominacion]');
    const den  = parseInt(row.dataset.denominacion);
    const cant = Math.max(0, parseInt(input.value) || 0);
    const sub  = den * cant;

    row.querySelector('.subtotal-col').textContent = '$' + fmt(sub);
    recalcTotal();
}

function recalcTotal() {
    let total = 0;
    document.querySelectorAll('tr[data-denominacion]').forEach(row => {
        const den  = parseInt(row.dataset.denominacion);
        const cant = Math.max(0, parseInt(row.querySelector('input').value) || 0);
        total += den * cant;
    });
    document.getElementById('totalBase').textContent = '$' + fmt(total);
}
</script>

</body>
</html>

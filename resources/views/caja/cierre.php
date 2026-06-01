<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

$apertura       = $apertura ?? null;
$cierre         = $cierre ?? null;
$precalc        = $precalc ?? [];
$denominaciones = $denominaciones ?? [];
$dashboardUrl   = $dashboardUrl ?? '/dashboard';
$pageTitle      = $pageTitle ?? 'Cierre de Caja — Kokoro Pollo';

$p = fn(float $v) => '$' . number_format($v, 0, ',', '.');

require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-20" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-2xl mx-auto px-4">

    <h1 class="text-4xl font-black text-center mb-2 tracking-wide" style="color:var(--oro);">
        🔒 Cierre de Caja
    </h1>
    <p class="text-center text-sm mb-6" style="color:#9ca3af;">
        <?= date('l d \d\e F \d\e Y') ?>
    </p>

    <?php if ($cierre): ?>
    <!-- ── Cierre ya realizado ── -->
    <?php
        $sobrante = (float) $cierre['sobrante'];
        $faltante  = (float) $cierre['faltante'];
        $neto      = $sobrante > 0 ? $sobrante : -$faltante;
    ?>
    <div class="rounded-2xl shadow-xl p-6 mb-5" style="background-color:var(--rojo-card); border:2px solid var(--oro);">
        <div class="text-center mb-5">
            <div class="text-4xl mb-2">✅</div>
            <h2 class="text-2xl font-black" style="color:var(--oro);">Caja cerrada</h2>
            <p class="text-sm mt-1" style="color:#9ca3af;">
                Por <strong><?= View::escape($cierre['nombre_usuario']) ?></strong>
                a las <?= date('H:i', strtotime($cierre['created_at'])) ?>
            </p>
        </div>

        <!-- Resumen financiero -->
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="rounded-xl px-4 py-3" style="background-color:var(--rojo-deep); border:1px solid var(--rojo-mid);">
                <p class="text-xs font-bold uppercase mb-1" style="color:#9ca3af;">Ventas</p>
                <p class="text-xl font-black text-green-400"><?= $p((float)$cierre['ventas']) ?></p>
            </div>
            <div class="rounded-xl px-4 py-3" style="background-color:var(--rojo-deep); border:1px solid var(--rojo-mid);">
                <p class="text-xs font-bold uppercase mb-1" style="color:#9ca3af;">Otras entradas</p>
                <p class="text-xl font-black text-green-400"><?= $p((float)$cierre['otras_entradas']) ?></p>
            </div>
            <div class="rounded-xl px-4 py-3" style="background-color:var(--rojo-deep); border:1px solid var(--rojo-mid);">
                <p class="text-xs font-bold uppercase mb-1" style="color:#9ca3af;">Gastos caja</p>
                <p class="text-xl font-black" style="color:#fca5a5;"><?= $p((float)$cierre['gastos_caja']) ?></p>
            </div>
            <div class="rounded-xl px-4 py-3" style="background-color:var(--rojo-deep); border:1px solid var(--rojo-mid);">
                <p class="text-xs font-bold uppercase mb-1" style="color:#9ca3af;">Créditos empleados</p>
                <p class="text-xl font-black" style="color:#fca5a5;"><?= $p((float)$cierre['creditos_empleados']) ?></p>
            </div>
            <div class="rounded-xl px-4 py-3" style="background-color:var(--rojo-deep); border:1px solid var(--rojo-mid);">
                <p class="text-xs font-bold uppercase mb-1" style="color:#9ca3af;">ALSÉS</p>
                <p class="text-xl font-black" style="color:#fca5a5;"><?= $p((float)$cierre['alses']) ?></p>
            </div>
            <div class="rounded-xl px-4 py-3" style="background-color:var(--rojo-deep); border:1px solid var(--rojo-mid);">
                <p class="text-xs font-bold uppercase mb-1" style="color:#9ca3af;">Otras salidas</p>
                <p class="text-xl font-black" style="color:#fca5a5;"><?= $p((float)$cierre['otras_salidas']) ?></p>
            </div>
        </div>

        <!-- Resultado arqueo -->
        <div class="rounded-xl px-5 py-4 mb-4" style="background-color:var(--rojo-deep); border:2px solid var(--rojo-mid);">
            <div class="flex justify-between items-center mb-2">
                <span class="font-bold text-white">Dinero esperado</span>
                <span class="font-black text-xl" style="color:var(--oro);"><?= $p((float)$cierre['dinero_esperado']) ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="font-bold text-white">Dinero contado</span>
                <span class="font-black text-xl" style="color:var(--oro);"><?= $p((float)$cierre['dinero_contado']) ?></span>
            </div>
        </div>

        <?php if ($sobrante > 0): ?>
        <div class="rounded-xl px-5 py-4 text-center" style="background-color:#132a1e; border:2px solid #16a34a;">
            <p class="text-sm font-bold uppercase tracking-wider text-green-400 mb-1">Sobrante</p>
            <p class="text-4xl font-black text-green-400"><?= $p($sobrante) ?></p>
        </div>
        <?php elseif ($faltante > 0): ?>
        <div class="rounded-xl px-5 py-4 text-center" style="background-color:#4a0e0e; border:2px solid #b91c1c;">
            <p class="text-sm font-bold uppercase tracking-wider mb-1" style="color:#fca5a5;">Faltante</p>
            <p class="text-4xl font-black" style="color:#fca5a5;"><?= $p($faltante) ?></p>
        </div>
        <?php else: ?>
        <div class="rounded-xl px-5 py-4 text-center" style="background-color:#132a1e; border:2px solid #16a34a;">
            <p class="text-2xl font-black text-green-400">✓ Cuadre perfecto</p>
        </div>
        <?php endif; ?>

        <?php if (!empty($cierre['observaciones'])): ?>
        <p class="mt-4 text-sm italic text-center" style="color:#9ca3af;">"<?= View::escape($cierre['observaciones']) ?>"</p>
        <?php endif; ?>
    </div>

    <div class="text-center">
        <a href="/caja" class="font-black text-lg px-8 py-4 rounded-xl btn-primary inline-block">← Ir a Caja</a>
    </div>

    <?php else: ?>
    <!-- ── Formulario de cierre ── -->

    <!-- Info de apertura -->
    <div class="rounded-xl px-5 py-3 mb-5 flex items-center gap-4" style="background-color:#132a1e; border:1px solid #1d6b3a;">
        <span class="text-2xl">🔓</span>
        <div>
            <p class="text-sm font-bold" style="color:#4ade80;">
                Apertura registrada — Base: <?= $p((float)$apertura['base_inicial']) ?>
            </p>
            <p class="text-xs" style="color:#9ca3af;">
                <?= View::escape($apertura['nombre_usuario']) ?> · <?= date('H:i', strtotime($apertura['created_at'])) ?>
            </p>
        </div>
    </div>

    <form method="POST" action="/caja/cierre" id="formCierre">
        <?= Csrf::field() ?>

        <!-- Sección 1: Movimientos del día (precalculados + editables) -->
        <div class="rounded-2xl shadow-xl p-5 mb-5" style="background-color:var(--rojo-card);">
            <h2 class="font-black text-lg mb-4 uppercase tracking-wider" style="color:var(--oro);">
                📊 Movimientos del día
            </h2>
            <p class="text-xs mb-4" style="color:#9ca3af;">Valores precalculados automáticamente. Ajusta si es necesario.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php
                $campos = [
                    ['ventas',            '▲ Ventas liquidadas',  $precalc['ventas'] ?? 0,          'text-green-400'],
                    ['otras_entradas',    '▲ Otras entradas',     $precalc['otras_entradas'] ?? 0,  'text-green-400'],
                    ['gastos_caja',       '▼ Gastos de caja',     $precalc['gastos_caja'] ?? 0,     '#fca5a5'],
                    ['creditos_empleados','▼ Créditos empleados', 0,                                '#fca5a5'],
                    ['alses',             '▼ ALSÉS',              0,                                '#fca5a5'],
                    ['otras_salidas',     '▼ Otras salidas',      0,                                '#fca5a5'],
                ];
                foreach ($campos as [$name, $label, $default, $color]):
                ?>
                <div>
                    <label class="block text-sm font-bold mb-1" style="color:<?= $color ?>;"><?= $label ?></label>
                    <input type="number" name="<?= $name ?>" min="0" step="1"
                           value="<?= number_format((float)$default, 0, '', '') ?>"
                           class="input-dark w-full text-lg font-bold px-4 py-3 rounded-xl"
                           oninput="recalcEsperado()">
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 px-4 py-3 rounded-xl flex justify-between items-center"
                 style="background-color:var(--rojo-deep); border:2px solid var(--rojo-mid);">
                <span class="font-bold text-white">Dinero esperado en caja</span>
                <span id="dineroEsperado" class="font-black text-2xl" style="color:var(--oro);">
                    <?= $p((float)$apertura['base_inicial'] + ($precalc['ventas'] ?? 0) + ($precalc['otras_entradas'] ?? 0) - ($precalc['gastos_caja'] ?? 0)) ?>
                </span>
            </div>
        </div>

        <!-- Sección 2: Arqueo físico -->
        <div class="rounded-2xl shadow-xl overflow-hidden mb-5" style="background-color:var(--rojo-card);">
            <div class="px-5 py-4" style="background-color:var(--rojo-mid);">
                <h2 class="font-black text-lg uppercase tracking-wider" style="color:var(--oro);">
                    💵 Arqueo físico — Conteo de caja
                </h2>
            </div>
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
                        <td class="px-5 py-3 font-semibold">$<?= number_format($valor, 0, ',', '.') ?></td>
                        <td class="px-5 py-3 text-center">
                            <input type="number" name="den_<?= $valor ?>"
                                   min="0" value="0"
                                   class="input-dark text-center font-bold text-lg rounded-xl px-3 py-2 w-24"
                                   onchange="recalcArqueo(this)"
                                   oninput="recalcArqueo(this)">
                        </td>
                        <td class="px-5 py-3 text-right font-black subtotal-col" style="color:var(--oro);">$0</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background-color:var(--rojo-mid);">
                        <td class="px-5 py-4 font-black text-white" colspan="2">TOTAL CONTADO</td>
                        <td class="px-5 py-4 text-right font-black text-2xl" style="color:var(--oro);" id="totalContado">$0</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Sección 3: Resultado en tiempo real -->
        <div id="panelResultado" class="rounded-2xl p-5 mb-5 text-center" style="background-color:var(--rojo-card); border:2px solid var(--rojo-mid);">
            <p class="text-sm font-bold uppercase tracking-wider mb-3" style="color:#9ca3af;">Resultado del arqueo</p>
            <div class="flex justify-around gap-4 flex-wrap">
                <div>
                    <p class="text-xs uppercase" style="color:#9ca3af;">Esperado</p>
                    <p id="resEsperado" class="text-2xl font-black" style="color:var(--oro);">$0</p>
                </div>
                <div>
                    <p class="text-xs uppercase" style="color:#9ca3af;">Contado</p>
                    <p id="resContado" class="text-2xl font-black" style="color:var(--oro);">$0</p>
                </div>
                <div>
                    <p class="text-xs uppercase" style="color:#9ca3af;" id="labelDif">Diferencia</p>
                    <p id="resDiferencia" class="text-2xl font-black" style="color:#9ca3af;">$0</p>
                </div>
            </div>
        </div>

        <!-- Observaciones -->
        <div class="rounded-2xl shadow-xl p-5 mb-5" style="background-color:var(--rojo-card);">
            <label class="block text-sm font-bold mb-2" style="color:var(--oro);">Observaciones (opcional)</label>
            <textarea name="observaciones" rows="2" maxlength="500"
                      placeholder="Ej: Se encontró sobrante de $5.000 en monedas..."
                      class="input-dark w-full px-4 py-3 rounded-xl text-base resize-none"></textarea>
        </div>

        <button type="submit"
                class="w-full font-black text-xl py-5 rounded-2xl shadow-xl uppercase tracking-wide btn-danger">
            🔒 Cerrar Caja del Día
        </button>
    </form>
    <?php endif; ?>

</div>

<a href="<?= View::escape($dashboardUrl) ?>"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg btn-secondary">
    ← Panel
</a>

<script>
const BASE_INICIAL = <?= (float)($apertura['base_inicial'] ?? 0) ?>;

function fmt(n) { return Math.round(n).toLocaleString('es-CO'); }

function getEsperado() {
    const get = name => Math.max(0, parseFloat(document.querySelector('[name="' + name + '"]')?.value) || 0);
    return BASE_INICIAL
        + get('ventas') + get('otras_entradas')
        - get('gastos_caja') - get('creditos_empleados') - get('alses') - get('otras_salidas');
}

function getContado() {
    let total = 0;
    document.querySelectorAll('tr[data-denominacion]').forEach(row => {
        const den  = parseInt(row.dataset.denominacion);
        const cant = Math.max(0, parseInt(row.querySelector('input').value) || 0);
        total += den * cant;
    });
    return total;
}

function recalcEsperado() {
    const esp = getEsperado();
    document.getElementById('dineroEsperado').textContent = '$' + fmt(esp);
    document.getElementById('resEsperado').textContent   = '$' + fmt(esp);
    calcDiferencia(esp, getContado());
}

function recalcArqueo(input) {
    const row  = input.closest('tr[data-denominacion]');
    const den  = parseInt(row.dataset.denominacion);
    const cant = Math.max(0, parseInt(input.value) || 0);
    row.querySelector('.subtotal-col').textContent = '$' + fmt(den * cant);

    const contado = getContado();
    document.getElementById('totalContado').textContent = '$' + fmt(contado);
    document.getElementById('resContado').textContent   = '$' + fmt(contado);
    calcDiferencia(getEsperado(), contado);
}

function calcDiferencia(esperado, contado) {
    const dif   = contado - esperado;
    const label = document.getElementById('labelDif');
    const el    = document.getElementById('resDiferencia');
    if (dif > 0) {
        label.textContent  = '✅ Sobrante';
        label.style.color  = '#4ade80';
        el.textContent     = '+$' + fmt(dif);
        el.style.color     = '#4ade80';
    } else if (dif < 0) {
        label.textContent  = '⚠️ Faltante';
        label.style.color  = '#fca5a5';
        el.textContent     = '-$' + fmt(Math.abs(dif));
        el.style.color     = '#fca5a5';
    } else {
        label.textContent  = '✓ Cuadre exacto';
        label.style.color  = '#4ade80';
        el.textContent     = '$0';
        el.style.color     = '#4ade80';
    }
}

// Inicializar esperado al cargar
document.addEventListener('DOMContentLoaded', () => {
    const esp = getEsperado();
    document.getElementById('dineroEsperado').textContent = '$' + fmt(esp);
    document.getElementById('resEsperado').textContent   = '$' + fmt(esp);
});
</script>

</body>
</html>

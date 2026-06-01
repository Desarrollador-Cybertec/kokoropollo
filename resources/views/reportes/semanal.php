<?php

declare(strict_types=1);

use App\Core\View;

$desde        = $desde        ?? date('Y-m-d', strtotime('monday this week'));
$hasta        = $hasta        ?? date('Y-m-d', strtotime('sunday this week'));
$resumen      = $resumen      ?? [];
$porDia       = $porDia       ?? [];
$topProd      = $topProd      ?? [];
$mejorDia     = $mejorDia     ?? null;
$peorDia      = $peorDia      ?? null;
$dashboardUrl = $dashboardUrl ?? '/dashboard';
$pageTitle    = $pageTitle    ?? 'Reporte Semanal — Kokoro Pollo';
$titulo       = $titulo       ?? 'Semanal';

$p  = fn(float $v) => '$' . number_format($v, 0, ',', '.');

require dirname(__DIR__) . '/partials/head.php';
?>

<body class="min-h-screen py-8 pb-20" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">
    <?php require dirname(__DIR__) . '/partials/toasts.php' ?>

    <div class="max-w-4xl mx-auto px-4">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-black tracking-wide" style="color:var(--oro);">📆 Reporte <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-sm mt-1" style="color:#9ca3af;">
                    <?= date('d/m/Y', strtotime($desde)) ?> — <?= date('d/m/Y', strtotime($hasta)) ?>
                </p>
            </div>
            <form method="GET" class="flex flex-wrap gap-2 items-end">
                <div>
                    <label class="text-xs font-bold block mb-1" style="color:#9ca3af;">Desde</label>
                    <input type="date" name="desde" value="<?= View::escape($desde) ?>"
                        class="input-dark px-3 py-2 rounded-xl text-sm">
                </div>
                <div>
                    <label class="text-xs font-bold block mb-1" style="color:#9ca3af;">Hasta</label>
                    <input type="date" name="hasta" value="<?= View::escape($hasta) ?>"
                        class="input-dark px-3 py-2 rounded-xl text-sm">
                </div>
                <button type="submit" class="font-bold px-5 py-2 rounded-xl btn-primary self-end">Ver</button>
            </form>
        </div>

        <!-- Accesos rápidos de período -->
        <?php
        [$lunSem, $domSem] = \App\Models\Reporte::semanaActual();
        [$q1, $q2]         = \App\Models\Reporte::quincenaActual();
        ?>
        <div class="flex flex-wrap gap-2 mb-6">
            <a href="/reportes/semanal?desde=<?= $lunSem ?>&hasta=<?= $domSem ?>"
                class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary">Esta semana</a>
            <a href="/reportes/semanal?desde=<?= $q1 ?>&hasta=<?= $q2 ?>"
                class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary">Esta quincena</a>
            <a href="/reportes/semanal?desde=<?= date('Y-m-d', strtotime('monday last week')) ?>&hasta=<?= date('Y-m-d', strtotime('sunday last week')) ?>"
                class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary">Semana pasada</a>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
            <?php
            $kpis = [
                ['Ventas totales',    $p((float)($resumen['total_ventas'] ?? 0)),  '#4ade80'],
                ['Utilidad estimada', $p((float)($resumen['utilidad'] ?? 0)),      'var(--oro)'],
                ['Margen',            number_format((float)($resumen['margen_pct'] ?? 0), 1, ',', '.') . '%', 'var(--oro)'],
                ['Total pedidos',     (string)(int)($resumen['total_pedidos'] ?? 0), 'white'],
                ['Promedio / día',    $p((float)($resumen['promedio_dia'] ?? 0)),  '#9ca3af'],
                ['Días con ventas',   (string)(int)($resumen['dias_con_ventas'] ?? 0), '#9ca3af'],
            ];
            foreach ($kpis as [$lbl, $val, $col]):
            ?>
                <div class="rounded-2xl px-4 py-4 text-center" style="background-color:var(--rojo-card); border:1px solid var(--rojo-mid);">
                    <p class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#9ca3af;"><?= $lbl ?></p>
                    <p class="text-xl font-black" style="color:<?= $col ?>;"><?= $val ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Mejor y peor día -->
        <?php if (!empty($porDia)): ?>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <?php
                $maxVentas = max(array_column($porDia, 'ventas'));
                $minVentas = min(array_column($porDia, 'ventas'));
                $mejorRow  = array_values(array_filter($porDia, fn($d) => (float)$d['ventas'] === (float)$maxVentas))[0] ?? null;
                $peorRow   = array_values(array_filter($porDia, fn($d) => (float)$d['ventas'] === (float)$minVentas))[0] ?? null;
                ?>
                <?php if ($mejorRow): ?>
                    <div class="rounded-2xl px-5 py-4" style="background-color:#132a1e; border:2px solid #16a34a;">
                        <p class="text-xs font-bold uppercase mb-1" style="color:#4ade80;">🏆 Mejor día</p>
                        <p class="font-black text-lg text-white"><?= date('l d/m', strtotime($mejorRow['dia'])) ?></p>
                        <p class="font-black text-2xl" style="color:#4ade80;"><?= $p((float)$mejorRow['ventas']) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($peorRow && $peorRow['dia'] !== ($mejorRow['dia'] ?? '')): ?>
                    <div class="rounded-2xl px-5 py-4" style="background-color:#4a0e0e; border:2px solid #b91c1c;">
                        <p class="text-xs font-bold uppercase mb-1" style="color:#fca5a5;">📉 Menor día</p>
                        <p class="font-black text-lg text-white"><?= date('l d/m', strtotime($peorRow['dia'])) ?></p>
                        <p class="font-black text-2xl" style="color:#fca5a5;"><?= $p((float)$peorRow['ventas']) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ventas por día (barras) -->
            <div class="rounded-2xl shadow-xl p-5 mb-6" style="background-color:var(--rojo-card);">
                <h2 class="font-black text-base uppercase tracking-wider mb-4" style="color:var(--oro);">📊 Ventas por día</h2>
                <?php $maxBar = max(array_column($porDia, 'ventas')) ?: 1; ?>
                <div class="space-y-3">
                    <?php foreach ($porDia as $d): ?>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold w-24 shrink-0" style="color:#d1d5db;"><?= date('D d/m', strtotime($d['dia'])) ?></span>
                            <div class="flex-1 rounded-full overflow-hidden" style="background-color:var(--rojo-deep); height:20px;">
                                <div style="width:<?= round((float)$d['ventas'] / $maxBar * 100) ?>%; height:100%; background-color:var(--oro); border-radius:9999px; transition:width .4s;"></div>
                            </div>
                            <span class="text-sm font-black w-28 text-right shrink-0" style="color:var(--oro);"><?= $p((float)$d['ventas']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Top productos -->
        <?php if (!empty($topProd)): ?>
            <div class="rounded-2xl shadow-xl overflow-hidden mb-6" style="background-color:var(--rojo-card);">
                <div class="px-5 py-3" style="background-color:var(--rojo-mid);">
                    <h2 class="font-black text-base uppercase tracking-wider" style="color:var(--oro);">🏆 Top productos del período</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Producto</th>
                            <th class="px-4 py-3 text-right">Uds</th>
                            <th class="px-4 py-3 text-right">Ingresos</th>
                            <th class="px-4 py-3 text-right">Margen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProd as $i => $prod):
                            $margen = (float)$prod['ingresos'] - (float)$prod['costo'];
                        ?>
                            <tr class="border-b text-white tr-dark" style="border-color:var(--rojo-mid);">
                                <td class="px-4 py-3" style="color:#9ca3af;"><?= $i + 1 ?></td>
                                <td class="px-4 py-3 font-semibold"><?= View::escape($prod['articulo']) ?></td>
                                <td class="px-4 py-3 text-right"><?= (int)$prod['uds_vendidas'] ?></td>
                                <td class="px-4 py-3 text-right font-bold text-green-400"><?= $p((float)$prod['ingresos']) ?></td>
                                <td class="px-4 py-3 text-right font-bold" style="color:var(--oro);"><?= $p($margen) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="rounded-2xl p-8 text-center mb-6" style="background-color:var(--rojo-card);">
                <p class="text-lg" style="color:#9ca3af;">Sin ventas en este período</p>
            </div>
        <?php endif; ?>

        <div class="flex gap-3 flex-wrap">
            <a href="/reportes" class="font-bold px-6 py-3 rounded-xl btn-secondary inline-block">← Reportes</a>
            <a href="/reportes/semanal?desde=<?= View::escape($desde) ?>&hasta=<?= View::escape($hasta) ?>&export=csv"
                class="font-bold px-5 py-3 rounded-xl btn-secondary inline-block">📥 CSV</a>
            <a href="/reportes/semanal?desde=<?= View::escape($desde) ?>&hasta=<?= View::escape($hasta) ?>&export=xls"
                class="font-bold px-5 py-3 rounded-xl btn-secondary inline-block">📊 Excel</a>
        </div>
    </div>
</body>

</html>
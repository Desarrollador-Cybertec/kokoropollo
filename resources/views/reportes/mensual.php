<?php
declare(strict_types=1);
use App\Core\View;

$mes          = $mes          ?? date('Y-m');
$desde        = $desde        ?? date('Y-m-01');
$hasta        = $hasta        ?? date('Y-m-t');
$resumen      = $resumen      ?? [];
$porDia       = $porDia       ?? [];
$topProd      = $topProd      ?? [];
$dashboardUrl = $dashboardUrl ?? '/dashboard';
$pageTitle    = $pageTitle    ?? 'Reporte Mensual — Kokoro Pollo';

$p = fn(float $v) => '$' . number_format($v, 0, ',', '.');

require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-20" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">
<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-4xl mx-auto px-4">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-black tracking-wide" style="color:var(--oro);">🗓️ Reporte Mensual</h1>
            <p class="text-sm mt-1" style="color:#9ca3af;"><?= date('F Y', strtotime($desde)) ?></p>
        </div>
        <form method="GET" class="flex gap-2 items-center">
            <input type="month" name="mes" value="<?= View::escape($mes) ?>"
                   class="input-dark px-4 py-2 rounded-xl text-base font-semibold">
            <button type="submit" class="font-bold px-5 py-2 rounded-xl btn-primary">Ver</button>
        </form>
    </div>

    <!-- Navegación meses -->
    <?php
    $mesPrev = date('Y-m', strtotime($desde . ' -1 month'));
    $mesSig  = date('Y-m', strtotime($desde . ' +1 month'));
    ?>
    <div class="flex gap-2 mb-6">
        <a href="/reportes/mensual?mes=<?= $mesPrev ?>" class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary">← <?= date('M Y', strtotime($mesPrev.'-01')) ?></a>
        <?php if ($mesSig <= date('Y-m')): ?>
        <a href="/reportes/mensual?mes=<?= $mesSig ?>"  class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary"><?= date('M Y', strtotime($mesSig.'-01')) ?> →</a>
        <?php endif; ?>
    </div>

    <!-- KPIs principales -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <?php
        $totalVentas = (float)($resumen['total_ventas'] ?? 0);
        $utilidad    = (float)($resumen['utilidad'] ?? 0);
        $gastos      = (float)($resumen['gastos'] ?? 0);
        $creditos    = (float)($resumen['creditos'] ?? 0);
        $kpis = [
            ['Ventas del mes',    $p($totalVentas),                                           '#4ade80'],
            ['Costo productos',   $p((float)($resumen['costo_ventas']??0)),                   '#fca5a5'],
            ['Utilidad estimada', $p($utilidad),                                              'var(--oro)'],
            ['Gastos operativos', $p($gastos),                                                '#fca5a5'],
            ['Créditos entregados', $p($creditos),                                            '#fbbf24'],
            ['Total pedidos',     (string)(int)($resumen['total_pedidos']??0),                'white'],
        ];
        foreach ($kpis as [$lbl, $val, $col]):
        ?>
        <div class="rounded-2xl px-4 py-4 text-center" style="background-color:var(--rojo-card); border:1px solid var(--rojo-mid);">
            <p class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#9ca3af;"><?= $lbl ?></p>
            <p class="text-xl font-black" style="color:<?= $col ?>;"><?= $val ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Resumen financiero -->
    <div class="rounded-2xl shadow-xl p-5 mb-6" style="background-color:var(--rojo-card);">
        <h2 class="font-black text-base uppercase tracking-wider mb-4" style="color:var(--oro);">📋 Resumen financiero del mes</h2>
        <div class="space-y-3">
            <?php
            $filas = [
                ['+ Ventas totales',       $totalVentas,    '#4ade80'],
                ['+ Ingresos manuales',    (float)($resumen['ingresos_manual'] ?? 0), '#4ade80'],
                ['− Gastos operativos',    $gastos,         '#fca5a5'],
                ['− Créditos empleados',   $creditos,       '#fca5a5'],
                ['− ALSÉS',               (float)($resumen['alses'] ?? 0), '#94a3b8'],
                ['= Flujo neto estimado',  $totalVentas - $gastos - $creditos, 'var(--oro)'],
            ];
            foreach ($filas as [$lbl, $val, $col]):
            ?>
            <div class="flex justify-between items-center px-2 py-1 border-b" style="border-color:var(--rojo-mid);">
                <span class="text-sm font-semibold" style="color:#d1d5db;"><?= $lbl ?></span>
                <span class="font-black" style="color:<?= $col ?>;"><?= $p($val) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Ventas por día del mes -->
    <?php if (!empty($porDia)): ?>
    <div class="rounded-2xl shadow-xl p-5 mb-6" style="background-color:var(--rojo-card);">
        <h2 class="font-black text-base uppercase tracking-wider mb-4" style="color:var(--oro);">📊 Ventas por día</h2>
        <?php $maxBar = max(array_column($porDia, 'ventas')) ?: 1; ?>
        <div class="space-y-2">
        <?php foreach ($porDia as $d): ?>
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold w-20 shrink-0" style="color:#d1d5db;"><?= date('d D', strtotime($d['dia'])) ?></span>
                <div class="flex-1 rounded-full overflow-hidden" style="background-color:var(--rojo-deep); height:14px;">
                    <div style="width:<?= round((float)$d['ventas']/$maxBar*100) ?>%; height:100%; background-color:var(--oro); border-radius:9999px;"></div>
                </div>
                <span class="text-xs font-black w-24 text-right shrink-0" style="color:var(--oro);"><?= $p((float)$d['ventas']) ?></span>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top productos del mes -->
    <?php if (!empty($topProd)): ?>
    <div class="rounded-2xl shadow-xl overflow-hidden mb-6" style="background-color:var(--rojo-card);">
        <div class="px-5 py-3" style="background-color:var(--rojo-mid);">
            <h2 class="font-black text-base uppercase tracking-wider" style="color:var(--oro);">🏆 Top productos del mes</h2>
        </div>
        <table class="w-full text-sm">
            <thead><tr style="background-color:var(--rojo-mid); color:var(--oro);">
                <th class="px-4 py-3 text-left">#</th>
                <th class="px-4 py-3 text-left">Producto</th>
                <th class="px-4 py-3 text-right">Uds</th>
                <th class="px-4 py-3 text-right">Ingresos</th>
                <th class="px-4 py-3 text-right">Margen</th>
            </tr></thead>
            <tbody>
            <?php foreach ($topProd as $i => $prod): ?>
                <tr class="border-b text-white tr-dark" style="border-color:var(--rojo-mid);">
                    <td class="px-4 py-3" style="color:#9ca3af;"><?= $i+1 ?></td>
                    <td class="px-4 py-3 font-semibold"><?= View::escape($prod['articulo']) ?></td>
                    <td class="px-4 py-3 text-right"><?= (int)$prod['uds_vendidas'] ?></td>
                    <td class="px-4 py-3 text-right font-bold text-green-400"><?= $p((float)$prod['ingresos']) ?></td>
                    <td class="px-4 py-3 text-right font-bold" style="color:var(--oro);"><?= $p((float)$prod['ingresos'] - (float)$prod['costo']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <a href="/reportes" class="font-bold px-6 py-3 rounded-xl btn-secondary inline-block">← Reportes</a>
</div>
</body>
</html>

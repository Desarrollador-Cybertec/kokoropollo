<?php
declare(strict_types=1);
use App\Core\View;

$fecha        = $fecha        ?? date('Y-m-d');
$resumen      = $resumen      ?? [];
$porHora      = $porHora      ?? [];
$topProd      = $topProd      ?? [];
$dashboardUrl = $dashboardUrl ?? '/dashboard';
$pageTitle    = $pageTitle    ?? 'Reporte Diario — Kokoro Pollo';

$p  = fn(float $v) => '$' . number_format($v, 0, ',', '.');
$pp = fn(float $v, int $d = 0) => number_format($v, $d, ',', '.');

require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-20" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">
<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-4xl mx-auto px-4">

    <!-- Header con selector de fecha -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-black tracking-wide" style="color:var(--oro);">📅 Reporte Diario</h1>
            <p class="text-sm mt-1" style="color:#9ca3af;"><?= date('l d \d\e F \d\e Y', strtotime($fecha)) ?></p>
        </div>
        <form method="GET" class="flex gap-2 items-center">
            <input type="date" name="fecha" value="<?= View::escape($fecha) ?>"
                   class="input-dark px-4 py-2 rounded-xl text-base font-semibold">
            <button type="submit" class="font-bold px-5 py-2 rounded-xl btn-primary">Ver</button>
        </form>
    </div>

    <!-- KPIs principales -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <?php
        $kpis = [
            ['Ventas totales', $p((float)($resumen['total_ventas']??0)), '#4ade80', 'text-green-400'],
            ['Costo productos', $p((float)($resumen['costo_ventas']??0)), '#fca5a5', ''],
            ['Utilidad estimada', $p((float)($resumen['utilidad']??0)), 'var(--oro)', ''],
            ['Margen', $pp((float)($resumen['margen_pct']??0),1).'%', 'var(--oro)', ''],
        ];
        foreach ($kpis as [$label, $val, $color, $cls]):
        ?>
        <div class="rounded-2xl px-4 py-4 text-center" style="background-color:var(--rojo-card); border:1px solid var(--rojo-mid);">
            <p class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#9ca3af;"><?= $label ?></p>
            <p class="text-xl font-black <?= $cls ?>" style="color:<?= $cls ? '' : $color ?>"><?= $val ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pedidos y tipos -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl px-4 py-4 text-center" style="background-color:var(--rojo-card); border:1px solid var(--rojo-mid);">
            <p class="text-xs font-bold uppercase mb-1" style="color:#9ca3af;">Total pedidos</p>
            <p class="text-3xl font-black" style="color:var(--oro);"><?= (int)($resumen['total_pedidos']??0) ?></p>
        </div>
        <div class="rounded-2xl px-4 py-4 text-center" style="background-color:var(--rojo-card); border:1px solid var(--rojo-mid);">
            <p class="text-xs font-bold uppercase mb-1" style="color:#9ca3af;">🏠 Local</p>
            <p class="text-3xl font-black text-white"><?= (int)($resumen['pedidos_local']??0) ?></p>
        </div>
        <div class="rounded-2xl px-4 py-4 text-center" style="background-color:#064e3b; border:1px solid #34d399;">
            <p class="text-xs font-bold uppercase mb-1" style="color:#34d399;">🛵 Para llevar</p>
            <p class="text-3xl font-black" style="color:#34d399;"><?= (int)($resumen['pedidos_llevar']??0) ?></p>
        </div>
    </div>

    <!-- Movimientos de caja del día -->
    <div class="rounded-2xl shadow-xl p-5 mb-6" style="background-color:var(--rojo-card);">
        <h2 class="font-black text-base uppercase tracking-wider mb-4" style="color:var(--oro);">💰 Movimientos de caja</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php
            $movs = [
                ['Ingresos manuales', (float)($resumen['ingresos_manual']??0), '#4ade80'],
                ['Gastos operativos', (float)($resumen['gastos']??0), '#fca5a5'],
                ['Créditos empleados', (float)($resumen['creditos']??0), '#fbbf24'],
                ['ALSÉS', (float)($resumen['alses']??0), '#94a3b8'],
            ];
            foreach ($movs as [$lbl, $val, $col]):
            ?>
            <div class="rounded-xl px-3 py-3 text-center" style="background-color:var(--rojo-deep);">
                <p class="text-xs font-bold mb-1" style="color:#9ca3af;"><?= $lbl ?></p>
                <p class="text-lg font-black" style="color:<?= $col ?>;"><?= $p($val) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Ventas por hora -->
    <?php if (!empty($porHora)): ?>
    <div class="rounded-2xl shadow-xl overflow-hidden mb-6" style="background-color:var(--rojo-card);">
        <div class="px-5 py-3" style="background-color:var(--rojo-mid);">
            <h2 class="font-black text-base uppercase tracking-wider" style="color:var(--oro);">🕐 Ventas por hora</h2>
        </div>
        <?php $maxVenta = max(array_column($porHora, 'total')) ?: 1; ?>
        <div class="p-4 space-y-2">
        <?php foreach ($porHora as $h): ?>
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold w-12 text-right" style="color:#9ca3af;"><?= str_pad((string)(int)$h['hora'], 2, '0', STR_PAD_LEFT) ?>:00</span>
                <div class="flex-1 rounded-full overflow-hidden" style="background-color:var(--rojo-deep); height:18px;">
                    <div style="width:<?= round((float)$h['total']/$maxVenta*100) ?>%; height:100%; background-color:var(--oro); border-radius:9999px;"></div>
                </div>
                <span class="text-sm font-black w-28 text-right" style="color:var(--oro);"><?= $p((float)$h['total']) ?></span>
                <span class="text-xs w-16 text-right" style="color:#9ca3af;"><?= (int)$h['pedidos'] ?> pedidos</span>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top productos -->
    <?php if (!empty($topProd)): ?>
    <div class="rounded-2xl shadow-xl overflow-hidden mb-6" style="background-color:var(--rojo-card);">
        <div class="px-5 py-3" style="background-color:var(--rojo-mid);">
            <h2 class="font-black text-base uppercase tracking-wider" style="color:var(--oro);">🏆 Top productos del día</h2>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                    <th class="px-4 py-3 text-left font-bold">#</th>
                    <th class="px-4 py-3 text-left font-bold">Producto</th>
                    <th class="px-4 py-3 text-right font-bold">Uds</th>
                    <th class="px-4 py-3 text-right font-bold">Ingresos</th>
                    <th class="px-4 py-3 text-right font-bold">Costo</th>
                    <th class="px-4 py-3 text-right font-bold">Margen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($topProd as $i => $prod):
                $ingr  = (float)$prod['ingresos'];
                $costo = (float)$prod['costo'];
                $margen = $ingr - $costo;
            ?>
                <tr class="border-b text-white tr-dark" style="border-color:var(--rojo-mid);">
                    <td class="px-4 py-3" style="color:#9ca3af;"><?= $i + 1 ?></td>
                    <td class="px-4 py-3 font-semibold"><?= View::escape($prod['articulo']) ?></td>
                    <td class="px-4 py-3 text-right"><?= (int)$prod['uds_vendidas'] ?></td>
                    <td class="px-4 py-3 text-right font-bold text-green-400"><?= $p($ingr) ?></td>
                    <td class="px-4 py-3 text-right" style="color:#fca5a5;"><?= $p($costo) ?></td>
                    <td class="px-4 py-3 text-right font-bold" style="color:var(--oro);"><?= $p($margen) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="rounded-2xl p-8 text-center mb-6" style="background-color:var(--rojo-card);">
        <p class="text-lg" style="color:#9ca3af;">Sin ventas registradas para esta fecha</p>
    </div>
    <?php endif; ?>

    <div class="flex gap-3 justify-center flex-wrap">
        <a href="/reportes" class="font-bold px-6 py-3 rounded-xl btn-secondary">← Reportes</a>
        <a href="/reportes/diario?fecha=<?= View::escape($fecha) ?>&export=csv"
           class="font-bold px-6 py-3 rounded-xl btn-secondary">📥 CSV</a>
        <a href="/reportes/diario?fecha=<?= date('Y-m-d', strtotime($fecha . ' -1 day')) ?>"
           class="font-bold px-6 py-3 rounded-xl btn-secondary">← Día anterior</a>
        <?php if ($fecha < date('Y-m-d')): ?>
        <a href="/reportes/diario?fecha=<?= date('Y-m-d', strtotime($fecha . ' +1 day')) ?>"
           class="font-bold px-6 py-3 rounded-xl btn-primary">Día siguiente →</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

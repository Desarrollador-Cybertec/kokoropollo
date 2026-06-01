<?php
declare(strict_types=1);
use App\Core\View;

$desde        = $desde        ?? date('Y-m-01');
$hasta        = $hasta        ?? date('Y-m-d');
$topProd      = $topProd      ?? [];
$dashboardUrl = $dashboardUrl ?? '/dashboard';
$pageTitle    = $pageTitle    ?? 'Top Productos — Kokoro Pollo';

$p = fn(float $v) => '$' . number_format($v, 0, ',', '.');

require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-20" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">
<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-4xl mx-auto px-4">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-black tracking-wide" style="color:var(--oro);">🏆 Top Productos</h1>
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

    <!-- Accesos rápidos -->
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="/reportes/productos?desde=<?= date('Y-m-d') ?>&hasta=<?= date('Y-m-d') ?>"
           class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary">Hoy</a>
        <a href="/reportes/productos?desde=<?= date('Y-m-01') ?>&hasta=<?= date('Y-m-d') ?>"
           class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary">Este mes</a>
        <a href="/reportes/productos?desde=<?= date('Y-01-01') ?>&hasta=<?= date('Y-m-d') ?>"
           class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary">Este año</a>
        <a href="/reportes/productos?desde=2020-01-01&hasta=<?= date('Y-m-d') ?>"
           class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary">Todo el tiempo</a>
    </div>

    <!-- Tabla ranking -->
    <?php if (!empty($topProd)): ?>
    <div class="rounded-2xl shadow-xl overflow-hidden mb-6" style="background-color:var(--rojo-card);">
        <table class="w-full text-sm">
            <thead>
                <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                    <th class="px-4 py-3 text-center font-bold">#</th>
                    <th class="px-4 py-3 text-left font-bold">Producto</th>
                    <th class="px-4 py-3 text-left font-bold">Categoría</th>
                    <th class="px-4 py-3 text-right font-bold">Pedidos</th>
                    <th class="px-4 py-3 text-right font-bold">Uds vendidas</th>
                    <th class="px-4 py-3 text-right font-bold">Ingresos</th>
                    <th class="px-4 py-3 text-right font-bold">Costo</th>
                    <th class="px-4 py-3 text-right font-bold">Margen</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $maxUds = max(array_column($topProd, 'uds_vendidas')) ?: 1;
            foreach ($topProd as $i => $prod):
                $ingr   = (float)$prod['ingresos'];
                $costo  = (float)$prod['costo'];
                $margen = $ingr - $costo;
                $pct    = round((int)$prod['uds_vendidas'] / $maxUds * 100);
                $medal  = match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '' };
            ?>
            <tr class="border-b text-white tr-dark" style="border-color:var(--rojo-mid);">
                <td class="px-4 py-3 text-center font-black" style="color:var(--oro);"><?= $medal ?: ($i+1) ?></td>
                <td class="px-4 py-3 font-semibold"><?= View::escape($prod['articulo']) ?></td>
                <td class="px-4 py-3 text-xs" style="color:#9ca3af;"><?= View::escape($prod['categoria']) ?></td>
                <td class="px-4 py-3 text-right"><?= (int)$prod['pedidos'] ?></td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <div class="w-16 rounded-full overflow-hidden" style="background-color:var(--rojo-deep); height:8px;">
                            <div style="width:<?= $pct ?>%; height:100%; background-color:var(--oro); border-radius:9999px;"></div>
                        </div>
                        <span class="font-bold"><?= (int)$prod['uds_vendidas'] ?></span>
                    </div>
                </td>
                <td class="px-4 py-3 text-right font-bold text-green-400"><?= $p($ingr) ?></td>
                <td class="px-4 py-3 text-right" style="color:#fca5a5;"><?= $p($costo) ?></td>
                <td class="px-4 py-3 text-right font-bold" style="color:var(--oro);"><?= $p($margen) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="rounded-2xl p-10 text-center" style="background-color:var(--rojo-card);">
        <p class="text-xl" style="color:#9ca3af;">Sin ventas en el período seleccionado</p>
    </div>
    <?php endif; ?>

    <a href="/reportes" class="font-bold px-6 py-3 rounded-xl btn-secondary inline-block">← Reportes</a>
</div>
</body>
</html>

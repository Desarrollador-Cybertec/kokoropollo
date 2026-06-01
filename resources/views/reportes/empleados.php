<?php

declare(strict_types=1);

use App\Core\View;

$desde        = $desde        ?? date('Y-m-01');
$hasta        = $hasta        ?? date('Y-m-d');
$usuario      = $usuario      ?? '';
$ranking      = $ranking      ?? [];
$porDia       = $porDia       ?? [];
$dashboardUrl = $dashboardUrl ?? '/dashboard';
$pageTitle    = $pageTitle    ?? 'Ventas por Empleado — Kokoro Pollo';

$p = fn(float $v) => '$' . number_format($v, 0, ',', '.');

require dirname(__DIR__) . '/partials/head.php';
?>

<body class="min-h-screen py-8 pb-20" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">
    <?php require dirname(__DIR__) . '/partials/toasts.php' ?>

    <div class="max-w-4xl mx-auto px-4">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-black tracking-wide" style="color:var(--oro);">👤 Ventas por Empleado</h1>
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
                <?php if ($usuario !== ''): ?>
                    <input type="hidden" name="usuario" value="<?= View::escape($usuario) ?>">
                <?php endif; ?>
                <button type="submit" class="font-bold px-5 py-2 rounded-xl btn-primary self-end">Ver</button>
            </form>
        </div>

        <!-- Accesos rápidos -->
        <div class="flex flex-wrap gap-2 mb-6">
            <a href="/reportes/empleados?desde=<?= date('Y-m-d') ?>&hasta=<?= date('Y-m-d') ?>"
                class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary">Hoy</a>
            <?php [$lun, $dom] = \App\Models\Reporte::semanaActual(); ?>
            <a href="/reportes/empleados?desde=<?= $lun ?>&hasta=<?= $dom ?>"
                class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary">Esta semana</a>
            <a href="/reportes/empleados?desde=<?= date('Y-m-01') ?>&hasta=<?= date('Y-m-d') ?>"
                class="text-xs font-bold px-4 py-2 rounded-xl btn-secondary">Este mes</a>
        </div>

        <!-- Tabla ranking -->
        <?php if (!empty($ranking)): ?>
            <div class="rounded-2xl shadow-xl overflow-hidden mb-6" style="background-color:var(--rojo-card);">
                <div class="px-5 py-3" style="background-color:var(--rojo-mid);">
                    <h2 class="font-black text-base uppercase tracking-wider" style="color:var(--oro);">
                        🏅 Ranking de empleados
                    </h2>
                </div>
                <?php
                $maxVentas = max(array_column($ranking, 'ventas')) ?: 1;
                ?>
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                            <th class="px-4 py-3 text-center">#</th>
                            <th class="px-4 py-3 text-left">Empleado</th>
                            <th class="px-4 py-3 text-right">Pedidos</th>
                            <th class="px-4 py-3 text-right">Ventas</th>
                            <th class="px-4 py-3 text-right">Ticket prom.</th>
                            <th class="px-4 py-3 text-right hidden sm:table-cell">🏠 Local</th>
                            <th class="px-4 py-3 text-right hidden sm:table-cell">🛵 Llevar</th>
                            <th class="px-4 py-3 text-right hidden sm:table-cell">Días</th>
                            <th class="px-4 py-3 text-center">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ranking as $i => $emp):
                            $pctBar = round((float)$emp['ventas'] / $maxVentas * 100);
                            $esSeleccionado = $usuario === $emp['usuario'];
                        ?>
                            <tr class="border-b tr-dark <?= $esSeleccionado ? 'ring-2 ring-yellow-500' : '' ?>"
                                style="border-color:var(--rojo-mid); <?= $esSeleccionado ? 'background-color:var(--rojo-alt);' : '' ?>">
                                <td class="px-4 py-3 text-center font-black" style="color:var(--oro);"><?= $i + 1 ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-white"><?= View::escape($emp['usuario']) ?></div>
                                    <div class="mt-1 rounded-full overflow-hidden" style="background-color:var(--rojo-deep); height:6px;">
                                        <div style="width:<?= $pctBar ?>%; height:100%; background-color:var(--oro); border-radius:9999px;"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-white"><?= (int)$emp['pedidos'] ?></td>
                                <td class="px-4 py-3 text-right font-black text-green-400"><?= $p((float)$emp['ventas']) ?></td>
                                <td class="px-4 py-3 text-right" style="color:var(--oro);"><?= $p((float)$emp['ticket_promedio']) ?></td>
                                <td class="px-4 py-3 text-right text-white hidden sm:table-cell"><?= (int)$emp['pedidos_local'] ?></td>
                                <td class="px-4 py-3 text-right text-white hidden sm:table-cell"><?= (int)$emp['pedidos_llevar'] ?></td>
                                <td class="px-4 py-3 text-right text-white hidden sm:table-cell"><?= (int)$emp['dias_activo'] ?></td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($esSeleccionado): ?>
                                        <a href="/reportes/empleados?desde=<?= View::escape($desde) ?>&hasta=<?= View::escape($hasta) ?>"
                                            class="text-xs font-bold px-3 py-1 rounded-lg btn-secondary">✕ Cerrar</a>
                                    <?php else: ?>
                                        <a href="/reportes/empleados?desde=<?= View::escape($desde) ?>&hasta=<?= View::escape($hasta) ?>&usuario=<?= urlencode($emp['usuario']) ?>"
                                            class="text-xs font-bold px-3 py-1 rounded-lg btn-primary">📊 Ver</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="rounded-2xl p-10 text-center mb-6" style="background-color:var(--rojo-card);">
                <p class="text-xl" style="color:#9ca3af;">Sin ventas en el período seleccionado</p>
            </div>
        <?php endif; ?>

        <!-- Detalle por día de un empleado -->
        <?php if ($usuario !== '' && !empty($porDia)): ?>
            <div class="rounded-2xl shadow-xl p-5 mb-6" style="background-color:var(--rojo-card); border:2px solid var(--oro);">
                <h2 class="font-black text-base uppercase tracking-wider mb-4" style="color:var(--oro);">
                    📅 Detalle por día — <?= View::escape($usuario) ?>
                </h2>
                <?php $maxBar = max(array_column($porDia, 'ventas')) ?: 1; ?>
                <div class="space-y-2">
                    <?php foreach ($porDia as $d): ?>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-semibold w-24 shrink-0" style="color:#d1d5db;">
                                <?= date('D d/m', strtotime($d['dia'])) ?>
                            </span>
                            <div class="flex-1 rounded-full overflow-hidden" style="background-color:var(--rojo-deep); height:16px;">
                                <div style="width:<?= round((float)$d['ventas'] / $maxBar * 100) ?>%; height:100%; background-color:var(--oro); border-radius:9999px;"></div>
                            </div>
                            <span class="text-xs w-8 text-right shrink-0" style="color:#9ca3af;"><?= (int)$d['pedidos'] ?>p</span>
                            <span class="text-sm font-black w-28 text-right shrink-0" style="color:var(--oro);">
                                <?= $p((float)$d['ventas']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($usuario !== '' && empty($porDia)): ?>
            <div class="rounded-2xl p-6 text-center mb-6" style="background-color:var(--rojo-card);">
                <p style="color:#9ca3af;">Sin ventas para <?= View::escape($usuario) ?> en este período</p>
            </div>
        <?php endif; ?>

        <div class="flex gap-3 flex-wrap">
            <a href="/reportes" class="font-bold px-6 py-3 rounded-xl btn-secondary inline-block">← Reportes</a>
            <a href="/reportes/empleados?desde=<?= View::escape($desde) ?>&hasta=<?= View::escape($hasta) ?>&export=csv"
                class="font-bold px-6 py-3 rounded-xl btn-secondary inline-block">📥 CSV</a>
        </div>
    </div>
</body>

</html>
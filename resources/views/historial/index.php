<?php
declare(strict_types=1);
use App\Core\View;

$desde = isset($desde) ? (string) $desde : '';
$hasta = isset($hasta) ? (string) $hasta : '';
$registros = (isset($registros) && is_array($registros)) ? $registros : [];
$totalIngresos = isset($totalIngresos) ? (float) $totalIngresos : 0.0;
$totalRetiros = isset($totalRetiros) ? (float) $totalRetiros : 0.0;
$total = isset($total) ? (int) $total : 0;
$pagina = isset($pagina) ? (int) $pagina : 1;
$totalPaginas = isset($totalPaginas) ? (int) $totalPaginas : 1;

$pageTitle = 'Historial de Caja — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-28" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-5xl mx-auto px-4">

    <h1 class="text-4xl font-black text-center mb-8 tracking-wide" style="color:var(--oro);">
        📋 Historial de Movimientos
    </h1>

    <!-- Filtro de fechas -->
    <div class="rounded-2xl shadow-xl p-5 mb-5" style="background-color:var(--rojo-card);">
        <form method="GET" action="/historial" class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-sm font-bold mb-1" style="color:var(--oro);">Desde</label>
                <input type="date" name="desde"
                       value="<?= View::escape($desde ?? '') ?>"
                       class="input-dark text-lg font-semibold px-4 py-3 rounded-xl w-full">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1" style="color:var(--oro);">Hasta</label>
                <input type="date" name="hasta"
                       value="<?= View::escape($hasta ?? '') ?>"
                       class="input-dark text-lg font-semibold px-4 py-3 rounded-xl w-full">
            </div>
            <div class="flex items-end gap-2 mt-auto">
                <button type="submit" class="font-black text-lg px-6 py-3 rounded-xl btn-primary">
                    Filtrar
                </button>
                <?php if (!empty($desde) || !empty($hasta)): ?>
                    <a href="/historial"
                       class="font-bold text-lg px-6 py-3 rounded-xl text-center btn-secondary">
                        Limpiar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Resumen del período -->
    <?php if (!empty($registros)): ?>
    <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="rounded-xl px-4 py-3 text-center" style="background-color:#134e2a;">
            <div class="text-xs font-bold uppercase tracking-wider text-green-300 mb-1">Ingresos</div>
            <div class="text-xl font-black text-green-300">
                +$<?= number_format($totalIngresos, 0, ',', '.') ?>
            </div>
        </div>
        <div class="rounded-xl px-4 py-3 text-center" style="background-color:#4a0e0e;">
            <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#fca5a5;">Retiros</div>
            <div class="text-xl font-black" style="color:#fca5a5;">
                -$<?= number_format($totalRetiros, 0, ',', '.') ?>
            </div>
        </div>
        <div class="rounded-xl px-4 py-3 text-center" style="background-color:var(--rojo-card); border:1px solid var(--rojo-mid);">
            <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:var(--oro);">Neto</div>
            <div class="text-xl font-black" style="color:var(--oro);">
                $<?= number_format($totalIngresos - $totalRetiros, 0, ',', '.') ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabla -->
    <div class="rounded-2xl shadow-xl overflow-hidden mb-5" style="background-color:var(--rojo-card);">
        <div class="overflow-x-auto" style="max-height:520px; overflow-y:auto;">
            <table class="w-full text-base">
                <thead class="sticky top-0">
                    <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                        <th class="px-4 py-4 text-left font-bold">ID</th>
                        <th class="px-4 py-4 text-left font-bold">Fecha y hora</th>
                        <th class="px-4 py-4 text-left font-bold">Tipo</th>
                        <th class="px-4 py-4 text-left font-bold">Valor</th>
                        <th class="px-4 py-4 text-left font-bold">Concepto</th>
                        <th class="px-4 py-4 text-left font-bold">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($registros as $r): ?>
                    <tr class="border-b text-white tr-dark" style="border-color:var(--rojo-mid);">
                        <td class="px-4 py-3" style="color:#9ca3af;"><?= (int) $r['id'] ?></td>
                        <td class="px-4 py-3 text-sm" style="color:#d1d5db;">
                            <?= date('d/m/Y', strtotime($r['fecha'])) ?>
                            <span style="color:#9ca3af;"> <?= date('H:i', strtotime($r['fecha'])) ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($r['tipo'] === 'ingreso'): ?>
                                <span class="font-bold text-sm px-3 py-1 rounded-full"
                                      style="background-color:#134e2a; color:#4ade80;">▲ Ingreso</span>
                            <?php else: ?>
                                <span class="font-bold text-sm px-3 py-1 rounded-full"
                                      style="background-color:#4a0e0e; color:#fca5a5;">▼ Retiro</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 font-bold <?= $r['tipo'] === 'ingreso' ? 'text-green-400' : '' ?>"
                            <?= $r['tipo'] === 'retiro' ? 'style="color:#fca5a5;"' : '' ?>>
                            <?= $r['tipo'] === 'ingreso' ? '+' : '-' ?>$<?= number_format((float) $r['valor'], 0, ',', '.') ?>
                        </td>
                        <td class="px-4 py-3" style="color:#d1d5db;"><?= View::escape($r['concepto']) ?></td>
                        <td class="px-4 py-3 text-sm" style="color:#9ca3af;"><?= View::escape($r['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-12 text-xl" style="color:#9ca3af;">
                            Sin registros para este período
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
    <div class="flex items-center justify-between rounded-2xl px-5 py-4 mb-5"
         style="background-color:var(--rojo-card);">
        <span class="text-sm font-semibold" style="color:#9ca3af;">
            <?= number_format($total) ?> registros · Página <?= $pagina ?> de <?= $totalPaginas ?>
        </span>
        <div class="flex gap-2">
            <?php
            $qBase = http_build_query(array_filter(['desde' => $desde, 'hasta' => $hasta]));
            $qBase = $qBase ? '&' . $qBase : '';
            ?>
            <?php if ($pagina > 1): ?>
                <a href="/historial?pagina=<?= $pagina - 1 . $qBase ?>"
                   class="font-bold px-5 py-2 rounded-xl text-sm btn-secondary">← Anterior</a>
            <?php endif; ?>
            <?php if ($pagina < $totalPaginas): ?>
                <a href="/historial?pagina=<?= $pagina + 1 . $qBase ?>"
                   class="font-bold px-5 py-2 rounded-xl text-sm btn-primary">Siguiente →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Botón regresar -->
<a href="/caja"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg btn-primary">
    ← REGRESAR
</a>

</body>
</html>

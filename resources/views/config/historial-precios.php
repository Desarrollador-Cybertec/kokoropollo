<?php
declare(strict_types=1);
use App\Core\View;

$registros    = $registros    ?? [];
$total        = $total        ?? 0;
$totalPags    = $totalPags    ?? 1;
$pagina       = $pagina       ?? 1;
$dashboardUrl = $dashboardUrl ?? '/dashboard';
$pageTitle    = $pageTitle    ?? 'Historial de Precios — Kokoro Pollo';

require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-20" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">
<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-3xl mx-auto px-4">

    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-wide" style="color:var(--oro);">📈 Historial de Precios</h1>
            <p class="text-sm mt-1" style="color:#9ca3af;"><?= $total ?> cambio(s) registrado(s)</p>
        </div>
        <a href="/config" class="font-bold px-5 py-2 rounded-xl btn-secondary">← Configuración</a>
    </div>

    <?php if (!empty($registros)): ?>
    <div class="rounded-2xl shadow-xl overflow-hidden mb-6" style="background-color:var(--rojo-card);">
        <table class="w-full text-sm">
            <thead>
                <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                    <th class="px-4 py-3 text-left font-bold">Fecha</th>
                    <th class="px-4 py-3 text-left font-bold">Cambio</th>
                    <th class="px-4 py-3 text-left font-bold">Quien</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($registros as $r): ?>
            <tr class="border-b tr-dark" style="border-color:var(--rojo-mid);">
                <td class="px-4 py-3 whitespace-nowrap text-xs" style="color:#9ca3af;">
                    <?= date('d/m/Y H:i', strtotime($r['fecha'])) ?>
                </td>
                <td class="px-4 py-3 text-white font-semibold">
                    <?= View::escape($r['descripcion']) ?>
                </td>
                <td class="px-4 py-3 text-xs font-bold" style="color:var(--oro);">
                    <?= View::escape($r['usuario']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPags > 1): ?>
    <div class="flex gap-2 flex-wrap mb-6">
        <?php if ($pagina > 1): ?>
        <a href="?pagina=<?= $pagina - 1 ?>" class="font-bold px-4 py-2 rounded-xl btn-secondary">← Anterior</a>
        <?php endif; ?>
        <span class="px-4 py-2 rounded-xl font-bold text-sm" style="background-color:var(--rojo-card); color:#9ca3af;">
            Página <?= $pagina ?> de <?= $totalPags ?>
        </span>
        <?php if ($pagina < $totalPags): ?>
        <a href="?pagina=<?= $pagina + 1 ?>" class="font-bold px-4 py-2 rounded-xl btn-secondary">Siguiente →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="rounded-2xl p-12 text-center" style="background-color:var(--rojo-card);">
        <p class="text-5xl mb-4">📋</p>
        <p class="text-xl font-bold text-white">Sin cambios registrados</p>
        <p class="text-sm mt-2" style="color:#9ca3af;">
            Los cambios de precios se registrarán automáticamente a partir de ahora.
        </p>
    </div>
    <?php endif; ?>

</div>
</body>
</html>

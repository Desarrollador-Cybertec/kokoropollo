<?php
declare(strict_types=1);
use App\Core\View;

$registros    = $registros    ?? [];
$total        = $total        ?? 0;
$totalPags    = $totalPags    ?? 1;
$pagina       = $pagina       ?? 1;
$modulo       = $modulo       ?? '';
$usuario      = $usuario      ?? '';
$modulos      = $modulos      ?? [];
$porPagina    = $porPagina    ?? 100;
$dashboardUrl = $dashboardUrl ?? '/dashboard';
$pageTitle    = 'Auditoría — Kokoro Pollo';

require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-20" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">
<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-5xl mx-auto px-4">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-black tracking-wide" style="color:var(--oro);">🔍 Auditoría Operativa</h1>
            <p class="text-sm mt-1" style="color:#9ca3af;"><?= $total ?> registro(s) encontrado(s)</p>
        </div>
        <a href="<?= View::escape($dashboardUrl) ?>" class="font-bold px-5 py-2 rounded-xl btn-secondary">
            ← Panel
        </a>
    </div>

    <!-- Filtros -->
    <form method="GET" class="flex flex-wrap gap-3 items-end mb-6 rounded-2xl p-4"
          style="background-color:var(--rojo-card);">
        <div>
            <label class="text-xs font-bold block mb-1" style="color:#9ca3af;">Módulo</label>
            <select name="modulo" class="input-dark px-3 py-2 rounded-xl text-sm" style="color:var(--oro);">
                <option value="" style="background-color:var(--rojo-deep);">Todos</option>
                <?php foreach ($modulos as $m): ?>
                <option value="<?= View::escape($m) ?>"
                        style="background-color:var(--rojo-deep);"
                        <?= $modulo === $m ? 'selected' : '' ?>>
                    <?= View::escape(ucfirst($m)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-xs font-bold block mb-1" style="color:#9ca3af;">Usuario</label>
            <input type="text" name="usuario" value="<?= View::escape($usuario) ?>"
                   placeholder="Filtrar por usuario..."
                   class="input-dark px-3 py-2 rounded-xl text-sm">
        </div>
        <input type="hidden" name="pagina" value="1">
        <button type="submit" class="font-bold px-5 py-2 rounded-xl btn-primary self-end">Filtrar</button>
        <?php if ($modulo !== '' || $usuario !== ''): ?>
        <a href="/auditoria" class="font-bold px-4 py-2 rounded-xl btn-secondary self-end text-sm">✕ Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- Tabla -->
    <?php if (!empty($registros)): ?>
    <div class="rounded-2xl shadow-xl overflow-hidden mb-6" style="background-color:var(--rojo-card);">
        <table class="w-full text-sm">
            <thead>
                <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                    <th class="px-4 py-3 text-left font-bold">Fecha</th>
                    <th class="px-4 py-3 text-left font-bold">Módulo</th>
                    <th class="px-4 py-3 text-left font-bold">Acción</th>
                    <th class="px-4 py-3 text-left font-bold">Descripción</th>
                    <th class="px-4 py-3 text-left font-bold">Usuario</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($registros as $r):
                $accionColor = match($r['accion']) {
                    'crear'    => '#4ade80',
                    'editar'   => 'var(--oro)',
                    'eliminar' => '#fca5a5',
                    'pagar'    => '#93c5fd',
                    default    => 'white',
                };
                $accionBg = match($r['accion']) {
                    'crear'    => '#132a1e',
                    'editar'   => '#3a2a0f',
                    'eliminar' => '#4a0e0e',
                    'pagar'    => '#1e3a5f',
                    default    => 'var(--rojo-deep)',
                };
            ?>
            <tr class="border-b tr-dark" style="border-color:var(--rojo-mid);">
                <td class="px-4 py-3 whitespace-nowrap text-xs" style="color:#9ca3af;">
                    <?= date('d/m/y H:i', strtotime($r['fecha'])) ?>
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs font-bold px-2 py-1 rounded-lg"
                          style="background-color:var(--rojo-mid); color:var(--oro);">
                        <?= View::escape($r['modulo']) ?>
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs font-bold px-2 py-1 rounded-lg"
                          style="background-color:<?= $accionBg ?>; color:<?= $accionColor ?>;">
                        <?= View::escape($r['accion']) ?>
                    </span>
                </td>
                <td class="px-4 py-3 text-white"><?= View::escape($r['descripcion']) ?></td>
                <td class="px-4 py-3 text-xs font-semibold" style="color:#9ca3af;">
                    <?= View::escape($r['usuario']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($totalPags > 1): ?>
    <div class="flex gap-2 flex-wrap mb-6">
        <?php if ($pagina > 1): ?>
        <a href="?pagina=<?= $pagina - 1 ?>&modulo=<?= urlencode($modulo) ?>&usuario=<?= urlencode($usuario) ?>"
           class="font-bold px-4 py-2 rounded-xl btn-secondary">← Anterior</a>
        <?php endif; ?>
        <span class="px-4 py-2 rounded-xl font-bold text-sm"
              style="background-color:var(--rojo-card); color:#9ca3af;">
            Página <?= $pagina ?> de <?= $totalPags ?>
        </span>
        <?php if ($pagina < $totalPags): ?>
        <a href="?pagina=<?= $pagina + 1 ?>&modulo=<?= urlencode($modulo) ?>&usuario=<?= urlencode($usuario) ?>"
           class="font-bold px-4 py-2 rounded-xl btn-secondary">Siguiente →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="rounded-2xl p-10 text-center" style="background-color:var(--rojo-card);">
        <p class="text-xl" style="color:#9ca3af;">Sin registros de auditoría</p>
        <p class="text-sm mt-2" style="color:#6b7280;">Los cambios en precios, inventario, usuarios y créditos aparecerán aquí.</p>
    </div>
    <?php endif; ?>

</div>
</body>
</html>

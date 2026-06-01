<?php
declare(strict_types=1);

$resumenHoy        = $resumenHoy        ?? [];
$resumenMes        = $resumenMes        ?? [];
$cajaTotal         = $cajaTotal         ?? 0.0;
$pendiente         = $pendiente         ?? 0.0;
$aperturaHoy       = $aperturaHoy       ?? false;
$cierreHoy         = $cierreHoy         ?? false;
$resumCreditos     = $resumCreditos     ?? [];
$stockCritico      = $stockCritico      ?? 0;
$pollosEnCiclo     = $pollosEnCiclo     ?? 0;
$pollosPorCiclo    = $pollosPorCiclo    ?? 1000;
$pctCondimentos    = $pctCondimentos    ?? 0;
$alertaCondimentos = $alertaCondimentos ?? null;
$mesDesde          = $mesDesde          ?? date('Y-m-01');
$mesHasta          = $mesHasta          ?? date('Y-m-d');
$ticketDia         = $ticketDia         ?? 0.0;
$topEmpleadoMes    = $topEmpleadoMes    ?? null;
$topProductoMes    = $topProductoMes    ?? null;
$variacionMes      = $variacionMes      ?? null;

$p   = fn(float $v) => '$' . number_format($v, 0, ',', '.');
$hoy = date('d/m/Y');

$pageTitle = 'Panel del Jefe — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen pb-20"
      style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<!-- Header -->
<header class="flex justify-between items-center px-6 py-4 border-b"
        style="border-color:var(--rojo-mid);">
    <h1 class="text-2xl md:text-3xl font-black tracking-widest uppercase"
        style="color:var(--oro);">KOKORO POLLO</h1>
    <div class="flex items-center gap-4">
        <span class="text-sm font-bold px-3 py-1 rounded-full"
              style="background-color:#78350f; color:#fde68a;">👑 Jefe</span>
        <img src="/img/logo.png" alt="Logo" class="h-12 w-auto">
    </div>
</header>

<main class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    <!-- Título + fecha -->
    <div class="flex justify-between items-center flex-wrap gap-3">
        <h2 class="text-2xl font-black" style="color:var(--oro);">📊 Resumen del negocio</h2>
        <span class="text-sm font-semibold px-4 py-2 rounded-xl"
              style="background-color:var(--rojo-card); color:#9ca3af;">📅 <?= $hoy ?></span>
    </div>

    <!-- ── KPIs del día ──────────────────────────────────────── -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php
        $kpisHoy = [
            ['Ventas hoy',      (float)($resumenHoy['total_ventas'] ?? 0),  '#4ade80',    $p],
            ['Utilidad estim.', (float)($resumenHoy['utilidad']     ?? 0),  'var(--oro)', $p],
            ['En caja ahora',   (float)$cajaTotal,                           'white',      $p],
            ['Pedidos hoy',     (float)($resumenHoy['total_pedidos']?? 0),  '#93c5fd',   fn($v) => (int)$v . ' ped.'],
        ];
        foreach ($kpisHoy as [$lbl, $val, $col, $fmt]):
        ?>
        <div class="rounded-2xl px-4 py-5 text-center"
             style="background-color:var(--rojo-card); border:1px solid var(--rojo-mid);">
            <p class="text-xs font-bold uppercase tracking-wider mb-2" style="color:#9ca3af;"><?= $lbl ?></p>
            <p class="text-2xl font-black" style="color:<?= $col ?>;"><?= $fmt($val) ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Estado operativo ─────────────────────────────────── -->
    <?php
    $hayAlertas = !$aperturaHoy || !$cierreHoy
        || ($resumCreditos['vencidos'] ?? 0) > 0
        || $stockCritico > 0
        || $alertaCondimentos !== null
        || $pendiente > 0;
    ?>
    <?php if ($hayAlertas): ?>
    <div class="rounded-2xl p-5 space-y-3"
         style="background-color:var(--rojo-card); border:2px solid #f59e0b;">
        <h3 class="font-black text-base uppercase tracking-wider" style="color:#fbbf24;">
            ⚠️ Alertas del día
        </h3>
        <div class="space-y-2">
            <?php if (!$aperturaHoy): ?>
            <a href="/caja" class="flex justify-between items-center px-4 py-3 rounded-xl hover:opacity-80 transition-opacity"
               style="background-color:#1a1000; border:1px solid #f59e0b;">
                <span class="font-bold text-yellow-300">🔓 Caja sin apertura hoy</span>
                <span class="text-xs font-bold text-yellow-400">Ir a Caja →</span>
            </a>
            <?php endif; ?>

            <?php if ($aperturaHoy && !$cierreHoy): ?>
            <a href="/caja" class="flex justify-between items-center px-4 py-3 rounded-xl hover:opacity-80 transition-opacity"
               style="background-color:#1a0000; border:1px solid #ef4444;">
                <span class="font-bold text-red-300">🔒 Pendiente cerrar caja</span>
                <span class="text-xs font-bold text-red-400">Ir a Caja →</span>
            </a>
            <?php endif; ?>

            <?php if (($resumCreditos['vencidos'] ?? 0) > 0): ?>
            <a href="/creditos" class="flex justify-between items-center px-4 py-3 rounded-xl hover:opacity-80 transition-opacity"
               style="background-color:#1a0000; border:1px solid #ef4444;">
                <span class="font-bold text-red-300">
                    💳 <?= (int)$resumCreditos['vencidos'] ?> crédito(s) vencido(s)
                </span>
                <span class="text-xs font-bold text-red-400">Ver →</span>
            </a>
            <?php endif; ?>

            <?php if ($stockCritico > 0): ?>
            <a href="/inventario" class="flex justify-between items-center px-4 py-3 rounded-xl hover:opacity-80 transition-opacity"
               style="background-color:#0c1a00; border:1px solid #84cc16;">
                <span class="font-bold" style="color:#bef264;">
                    📦 <?= $stockCritico ?> artículo(s) con stock bajo
                </span>
                <span class="text-xs font-bold" style="color:#bef264;">Ver →</span>
            </a>
            <?php endif; ?>

            <?php if ($alertaCondimentos !== null): ?>
            <?php
            $condColor  = $alertaCondimentos === 'agotado' ? '#ef4444' : ($alertaCondimentos === 'critica' ? '#fca5a5' : '#fbbf24');
            $condBg     = $alertaCondimentos === 'agotado' ? '#1a0000' : ($alertaCondimentos === 'critica' ? '#1a0000' : '#1a1000');
            $condBorder = $alertaCondimentos === 'agotado' ? '#ef4444' : ($alertaCondimentos === 'critica' ? '#ef4444' : '#f59e0b');
            $condIcon   = $alertaCondimentos === 'agotado' ? '🚨 CONDIMENTOS AGOTADOS' : ($alertaCondimentos === 'critica' ? '🔴 Condimentos críticos' : '⚠️ Preparar condimentos');
            ?>
            <a href="/config#condimentos" class="flex justify-between items-center px-4 py-3 rounded-xl hover:opacity-80 transition-opacity"
               style="background-color:<?= $condBg ?>; border:1px solid <?= $condBorder ?>;">
                <span class="font-bold" style="color:<?= $condColor ?>;">
                    <?= $condIcon ?> — <?= $pollosEnCiclo ?>/<?= $pollosPorCiclo ?> pollos (<?= $pctCondimentos ?>%)
                </span>
                <span class="text-xs font-bold" style="color:<?= $condColor ?>;">Gestionar →</span>
            </a>
            <?php endif; ?>

            <?php if ($pendiente > 0): ?>
            <a href="/ventas" class="flex justify-between items-center px-4 py-3 rounded-xl hover:opacity-80 transition-opacity"
               style="background-color:#1a1000; border:1px solid var(--oro);">
                <span class="font-bold" style="color:var(--oro);">
                    💵 <?= $p((float)$pendiente) ?> pendiente de liquidar
                </span>
                <span class="text-xs font-bold" style="color:var(--oro);">Ir a Ventas →</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Resumen del mes ──────────────────────────────────── -->
    <div class="rounded-2xl p-5" style="background-color:var(--rojo-card);">
        <?php
        $ventasMes    = (float)($resumenMes['total_ventas']  ?? 0);
        $pedidosMes   = (int)($resumenMes['total_pedidos']   ?? 0);
        $ticketMes    = $pedidosMes > 0 ? $ventasMes / $pedidosMes : 0.0;
        ?>
        <div class="flex justify-between items-start mb-4 flex-wrap gap-3">
            <h3 class="font-black text-base uppercase tracking-wider" style="color:var(--oro);">
                🗓️ Mes actual — <?= date('F Y', strtotime($mesDesde)) ?>
            </h3>
            <?php if ($variacionMes !== null): ?>
            <span class="text-sm font-black px-3 py-1 rounded-xl"
                  style="background-color:<?= $variacionMes >= 0 ? '#132a1e' : '#4a0e0e' ?>;
                         color:<?= $variacionMes >= 0 ? '#4ade80' : '#fca5a5' ?>;">
                <?= $variacionMes >= 0 ? '↑' : '↓' ?> <?= abs($variacionMes) ?>% vs mes anterior
            </span>
            <?php endif; ?>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <?php
            $kpisMes = [
                ['Ventas mes',   $ventasMes,                          '#4ade80',    $p],
                ['Utilidad mes', (float)($resumenMes['utilidad'] ?? 0), 'var(--oro)', $p],
                ['Pedidos mes',  (float)$pedidosMes,                  'white',      fn($v) => (int)$v . ' ped.'],
                ['Ticket prom.', $ticketMes,                          '#93c5fd',    $p],
            ];
            foreach ($kpisMes as [$lbl, $val, $col, $fmt]):
            ?>
            <div class="rounded-xl px-3 py-4 text-center"
                 style="background-color:var(--rojo-deep); border:1px solid var(--rojo-mid);">
                <p class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#9ca3af;"><?= $lbl ?></p>
                <p class="text-xl font-black" style="color:<?= $col ?>;"><?= $fmt($val) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Top empleado + Top producto del mes -->
        <?php if ($topEmpleadoMes || $topProductoMes): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php if ($topEmpleadoMes): ?>
            <div class="rounded-xl px-4 py-3 flex items-center gap-3"
                 style="background-color:var(--rojo-deep); border:1px solid var(--rojo-mid);">
                <span class="text-2xl">🏆</span>
                <div>
                    <p class="text-xs font-bold uppercase" style="color:#9ca3af;">Top empleado del mes</p>
                    <p class="font-black text-white"><?= htmlspecialchars($topEmpleadoMes['usuario'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs" style="color:#4ade80;"><?= $p((float)$topEmpleadoMes['ventas']) ?> · <?= (int)$topEmpleadoMes['pedidos'] ?> pedidos</p>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($topProductoMes): ?>
            <div class="rounded-xl px-4 py-3 flex items-center gap-3"
                 style="background-color:var(--rojo-deep); border:1px solid var(--rojo-mid);">
                <span class="text-2xl">🥇</span>
                <div>
                    <p class="text-xs font-bold uppercase" style="color:#9ca3af;">Top producto del mes</p>
                    <p class="font-black text-white"><?= htmlspecialchars($topProductoMes['articulo'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs" style="color:var(--oro);"><?= (int)$topProductoMes['uds_vendidas'] ?> uds · <?= $p((float)$topProductoMes['ingresos']) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Accesos rápidos ──────────────────────────────────── -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <?php
        $accesos = [
            ['/ventas',     '🛒', 'VENTAS'],
            ['/caja',       '💰', 'CAJA'],
            ['/caja',       '🔓', 'APERTURA'],
            ['/caja',       '🔒', 'CIERRE'],
            ['/reportes',   '📊', 'REPORTES'],
            ['/creditos',   '💳', 'CRÉDITOS'],
            ['/inventario', '📦', 'INVENTARIO'],
            ['/usuarios',   '👥', 'USUARIOS'],
            ['/config',     '⚙️', 'CONFIG'],
            ['/auditoria',  '🔍', 'AUDITORÍA'],
        ];
        foreach ($accesos as [$url, $icon, $label]):
        ?>
        <a href="<?= $url ?>"
           class="flex flex-col items-center gap-2 font-black text-base rounded-2xl py-6 px-3 shadow-lg transition-all hover:scale-105 active:scale-95 btn-primary">
            <span class="text-4xl"><?= $icon ?></span>
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

</main>

<!-- Logout -->
<a href="/logout"
   class="fixed bottom-5 right-5 font-bold text-lg px-5 py-3 rounded-xl transition-all btn-secondary"
   style="border:2px solid var(--oro); color:var(--oro);">
    Cerrar sesión
</a>

</body>
</html>

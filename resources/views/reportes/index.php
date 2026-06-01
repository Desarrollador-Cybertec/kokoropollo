<?php
declare(strict_types=1);
use App\Core\View;
$dashboardUrl = $dashboardUrl ?? '/dashboard';
$pageTitle    = $pageTitle ?? 'Reportes — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-20" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">
<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-2xl mx-auto px-4">
    <h1 class="text-4xl font-black text-center mb-2 tracking-wide" style="color:var(--oro);">📊 Reportes</h1>
    <p class="text-center text-sm mb-8" style="color:#9ca3af;">Análisis gerencial de la operación</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

        <a href="/reportes/diario"
           class="rounded-2xl p-7 shadow-xl flex flex-col items-center gap-3 text-center transition-all hover:scale-105 active:scale-95"
           style="background-color:var(--rojo-card); border:2px solid var(--rojo-mid);">
            <span class="text-5xl">📅</span>
            <h2 class="font-black text-xl" style="color:var(--oro);">Diario</h2>
            <p class="text-sm" style="color:#9ca3af;">Ventas, gastos, utilidad y top productos de un día</p>
        </a>

        <a href="/reportes/semanal"
           class="rounded-2xl p-7 shadow-xl flex flex-col items-center gap-3 text-center transition-all hover:scale-105 active:scale-95"
           style="background-color:var(--rojo-card); border:2px solid var(--rojo-mid);">
            <span class="text-5xl">📆</span>
            <h2 class="font-black text-xl" style="color:var(--oro);">Semanal</h2>
            <p class="text-sm" style="color:#9ca3af;">Acumulado semanal con mejor y peor día</p>
        </a>

        <a href="/reportes/mensual"
           class="rounded-2xl p-7 shadow-xl flex flex-col items-center gap-3 text-center transition-all hover:scale-105 active:scale-95"
           style="background-color:var(--rojo-card); border:2px solid var(--rojo-mid);">
            <span class="text-5xl">🗓️</span>
            <h2 class="font-black text-xl" style="color:var(--oro);">Mensual</h2>
            <p class="text-sm" style="color:#9ca3af;">Totales del mes con utilidad y gastos</p>
        </a>

        <a href="/reportes/productos"
           class="rounded-2xl p-7 shadow-xl flex flex-col items-center gap-3 text-center transition-all hover:scale-105 active:scale-95"
           style="background-color:var(--rojo-card); border:2px solid var(--rojo-mid);">
            <span class="text-5xl">🏆</span>
            <h2 class="font-black text-xl" style="color:var(--oro);">Top Productos</h2>
            <p class="text-sm" style="color:#9ca3af;">Ranking por unidades e ingresos en cualquier período</p>
        </a>

        <a href="/reportes/empleados"
           class="rounded-2xl p-7 shadow-xl flex flex-col items-center gap-3 text-center transition-all hover:scale-105 active:scale-95"
           style="background-color:var(--rojo-card); border:2px solid var(--rojo-mid);">
            <span class="text-5xl">👤</span>
            <h2 class="font-black text-xl" style="color:var(--oro);">Por Empleado</h2>
            <p class="text-sm" style="color:#9ca3af;">Ventas, pedidos y ticket promedio por persona</p>
        </a>

    </div>
</div>

<a href="<?= View::escape($dashboardUrl) ?>"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg btn-primary">
    ← Panel
</a>
</body>
</html>

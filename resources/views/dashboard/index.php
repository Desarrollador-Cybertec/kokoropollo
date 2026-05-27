<?php
declare(strict_types=1);

$pageTitle = 'Panel — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen flex flex-col"
      style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<!-- Header -->
<header class="flex justify-between items-center px-6 py-5 border-b"
        style="border-color:var(--rojo-mid);">
    <h1 class="text-3xl md:text-4xl font-black tracking-widest uppercase"
        style="color:var(--oro);">KOKORO POLLO</h1>
    <img src="/img/logo.png" alt="Logo" class="h-16 md:h-20 w-auto">
</header>

<!-- Main -->
<main class="flex-1 flex flex-col items-center justify-center p-6 gap-8">

    <p class="text-2xl md:text-3xl font-bold tracking-widest uppercase" style="color:var(--oro);">
        <?= $esAdmin ? 'Panel de Administración' : 'Panel de Empleado' ?>
    </p>

    <div class="rounded-2xl shadow-2xl p-8 w-full <?= $esAdmin ? 'max-w-2xl' : 'max-w-xl' ?>"
         style="background-color:rgba(74,14,14,0.8);">

        <div class="grid <?= $esAdmin ? 'grid-cols-2' : 'grid-cols-1 sm:grid-cols-3' ?> gap-5">

            <a href="/inventario"
               class="flex flex-col items-center gap-3 font-black text-xl rounded-2xl py-8 px-4 shadow-lg transition-all hover:scale-105 active:scale-95 btn-primary">
                <span class="text-5xl">📦</span>
                INVENTARIO
            </a>

            <?php if ($esAdmin): ?>
            <a href="/usuarios"
               class="flex flex-col items-center gap-3 font-black text-xl rounded-2xl py-8 px-4 shadow-lg transition-all hover:scale-105 active:scale-95 btn-primary">
                <span class="text-5xl">👥</span>
                USUARIOS
            </a>
            <?php endif; ?>

            <a href="/caja"
               class="flex flex-col items-center gap-3 font-black text-xl rounded-2xl py-8 px-4 shadow-lg transition-all hover:scale-105 active:scale-95 btn-primary">
                <span class="text-5xl">💰</span>
                CAJA
            </a>

            <a href="/ventas"
               class="flex flex-col items-center gap-3 font-black text-xl rounded-2xl py-8 px-4 shadow-lg transition-all hover:scale-105 active:scale-95 btn-primary">
                <span class="text-5xl">🛒</span>
                VENTAS
                <?php if ($totalDia > 0): ?>
                    <span class="text-sm font-semibold" style="opacity:.75;">
                        $<?= number_format((float) $totalDia, 0, ',', '.') ?> hoy
                    </span>
                <?php endif; ?>
            </a>

        </div>
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

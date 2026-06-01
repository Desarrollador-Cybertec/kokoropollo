<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

$pageTitle = 'Configuración — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<body class="bg-app min-h-screen py-8 pb-28">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-2xl mx-auto px-4">

    <h1 class="text-4xl font-black text-center mb-8 tracking-wide" style="color:var(--oro);">
        ⚙️ Configuración de Precios
    </h1>

    <form method="POST" action="/config" class="space-y-6">
        <?= Csrf::field() ?>

        <!-- Pollo Asado -->
        <div class="rounded-2xl shadow-xl p-6" style="background-color:var(--rojo-card);">
            <h2 class="text-xl font-black mb-5 flex items-center gap-2" style="color:var(--oro);">
                🍗 Pollo Asado — Precios por corte
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-1" style="color:var(--oro);">
                        ¼ de pollo (cuarto)
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-lg" style="color:var(--oro);">$</span>
                        <input type="number" name="precio_asado_cuarto" min="0" step="1"
                               value="<?= number_format((float)($precios['precio_asado_cuarto'] ?? 0), 0, '', '') ?>"
                               class="w-full text-xl font-bold pl-7 pr-4 py-3 rounded-xl input-dark">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1" style="color:var(--oro);">
                        ½ pollo (medio)
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-lg" style="color:var(--oro);">$</span>
                        <input type="number" name="precio_asado_medio" min="0" step="1"
                               value="<?= number_format((float)($precios['precio_asado_medio'] ?? 0), 0, '', '') ?>"
                               class="w-full text-xl font-bold pl-7 pr-4 py-3 rounded-xl input-dark">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1" style="color:var(--oro);">
                        1 pollo entero
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-lg" style="color:var(--oro);">$</span>
                        <input type="number" name="precio_asado_entero" min="0" step="1"
                               value="<?= number_format((float)($precios['precio_asado_entero'] ?? 0), 0, '', '') ?>"
                               class="w-full text-xl font-bold pl-7 pr-4 py-3 rounded-xl input-dark">
                    </div>
                </div>
            </div>
        </div>

        <!-- Pollo Broaster -->
        <div class="rounded-2xl shadow-xl p-6" style="background-color:var(--rojo-card);">
            <h2 class="text-xl font-black mb-5 flex items-center gap-2" style="color:var(--oro);">
                🍳 Pollo Broaster — Precios por corte
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-1" style="color:var(--oro);">
                        ¼ de pollo (cuarto)
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-lg" style="color:var(--oro);">$</span>
                        <input type="number" name="precio_broaster_cuarto" min="0" step="1"
                               value="<?= number_format((float)($precios['precio_broaster_cuarto'] ?? 0), 0, '', '') ?>"
                               class="w-full text-xl font-bold pl-7 pr-4 py-3 rounded-xl input-dark">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1" style="color:var(--oro);">
                        ½ pollo (medio)
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-lg" style="color:var(--oro);">$</span>
                        <input type="number" name="precio_broaster_medio" min="0" step="1"
                               value="<?= number_format((float)($precios['precio_broaster_medio'] ?? 0), 0, '', '') ?>"
                               class="w-full text-xl font-bold pl-7 pr-4 py-3 rounded-xl input-dark">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1" style="color:var(--oro);">
                        1 pollo entero
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-lg" style="color:var(--oro);">$</span>
                        <input type="number" name="precio_broaster_entero" min="0" step="1"
                               value="<?= number_format((float)($precios['precio_broaster_entero'] ?? 0), 0, '', '') ?>"
                               class="w-full text-xl font-bold pl-7 pr-4 py-3 rounded-xl input-dark">
                    </div>
                </div>
            </div>
        </div>

        <!-- Nota informativa -->
        <div class="rounded-xl px-5 py-4 text-sm font-semibold" style="background-color:var(--rojo-card); color:#9ca3af;">
            💡 Estos precios se aplican en el POS de Ventas según el corte seleccionado.
            El inventario descuenta automáticamente los cuartos correspondientes (¼=1 ud, ½=2 uds, entero=4 uds).
        </div>

        <button type="submit"
                class="w-full font-black text-xl py-4 rounded-xl btn-primary">
            💾 Guardar precios
        </button>
    </form>

</div>

<!-- Botón regresar -->
<a href="/dashboard"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg btn-primary">
    ← REGRESAR
</a>

</body>
</html>

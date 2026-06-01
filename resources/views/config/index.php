<?php
declare(strict_types=1);
use App\Core\{Csrf, View};
$empaqueActivo       = $empaqueActivo       ?? '0';
$empaqueInventarioId = $empaqueInventarioId ?? '0';
$inventarioItems     = $inventarioItems     ?? [];
$dashboardUrl        = $dashboardUrl        ?? '/dashboard';

$pageTitle = 'Configuración — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-28" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">

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

        <!-- Nota informativa -->
        <div class="rounded-xl px-5 py-4 text-sm font-semibold" style="background-color:var(--rojo-card); color:#9ca3af;">
            💡 Estos precios se aplican en el POS de Ventas según el corte seleccionado.
            El inventario descuenta automáticamente los cuartos correspondientes (¼=1 ud, ½=2 uds, entero=4 uds).
        </div>

        <!-- Capacidad de condimentos -->
        <div class="rounded-2xl shadow-xl p-6" style="background-color:var(--rojo-card);">
            <h2 class="text-xl font-black mb-2 flex items-center gap-2" style="color:var(--oro);">
                🧂 Capacidad de condimentos
            </h2>
            <p class="text-xs mb-4" style="color:#9ca3af;">Pollos que alcanza una preparación de condimentos antes de necesitar reponer.</p>
            <div class="flex items-center gap-4">
                <div class="relative flex-1 max-w-xs">
                    <input type="number" name="condimentos_pollos_por_ciclo" min="1" step="1"
                           value="<?= (int)($pollosPorCiclo ?? 1000) ?>"
                           class="w-full text-xl font-bold px-4 py-3 rounded-xl input-dark">
                </div>
                <span class="text-base font-semibold" style="color:#9ca3af;">pollos / ciclo</span>
            </div>
        </div>

        <!-- Empaque automático -->
        <div class="rounded-2xl shadow-xl p-6" style="background-color:var(--rojo-card);">
            <h2 class="text-xl font-black mb-2 flex items-center gap-2" style="color:var(--oro);">
                📦 Empaque automático
            </h2>
            <p class="text-xs mb-5" style="color:#9ca3af;">
                Descuenta 1 unidad del inventario por cada pedido <strong style="color:white;">Para llevar</strong>.
                Útil para cajas de empaque o bolsas.
            </p>
            <div class="flex items-center gap-3 mb-4">
                <input type="checkbox" id="chkEmpaque" name="empaque_activo" value="1"
                       <?= $empaqueActivo === '1' ? 'checked' : '' ?>
                       class="w-5 h-5 accent-yellow-500">
                <label for="chkEmpaque" class="text-base font-bold text-white cursor-pointer">
                    Activar descuento automático de empaque
                </label>
            </div>
            <div>
                <label class="text-sm font-bold block mb-2" style="color:var(--oro);">
                    Artículo de empaque (del inventario)
                </label>
                <select name="empaque_inventario_id"
                        class="w-full text-base px-4 py-3 rounded-xl input-dark"
                        style="color:var(--oro);">
                    <option value="0" style="background-color:var(--rojo-deep);">— Sin seleccionar —</option>
                    <?php foreach ($inventarioItems as $item): ?>
                    <option value="<?= (int)$item['id'] ?>"
                            style="background-color:var(--rojo-deep);"
                            <?= ((int)$empaqueInventarioId === (int)$item['id']) ? 'selected' : '' ?>>
                        <?= View::escape($item['articulo']) ?>
                        (<?= (int)$item['cantidad'] ?> en stock)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit"
                class="w-full font-black text-xl py-4 rounded-xl btn-primary">
            💾 Guardar configuración
        </button>
    </form>

    <!-- Sección condimentos — reinicio de ciclo -->
    <div id="condimentos" class="rounded-2xl shadow-xl p-6 mt-6" style="background-color:var(--rojo-card); border:2px solid #f59e0b;">
        <?php
            $pct = (float)($pctCondimentos ?? 0);
            $barColor = match(true) {
                $pct >= 100 => '#ef4444',
                $pct >= 80  => '#ef4444',
                $pct >= 50  => '#f59e0b',
                default     => '#4ade80',
            };
        ?>
        <h2 class="text-xl font-black mb-4 flex items-center gap-2" style="color:#fbbf24;">
            🧂 Estado del ciclo de condimentos
        </h2>

        <!-- Barra de progreso -->
        <div class="rounded-full overflow-hidden mb-3" style="background-color:var(--rojo-deep); height:12px;">
            <div style="width:<?= min(100, $pct) ?>%; height:100%; background-color:<?= $barColor ?>; transition:width .5s;"></div>
        </div>

        <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
            <div>
                <p class="text-2xl font-black" style="color:<?= $barColor ?>;">
                    <?= (int)($pollosEnCiclo ?? 0) ?> / <?= (int)($pollosPorCiclo ?? 1000) ?> pollos
                    <span class="text-lg">(<?= $pct ?>%)</span>
                </p>
                <p class="text-sm mt-1" style="color:#9ca3af;">
                    Acumulado total del sistema: <?= (int)(($cuartosTotal ?? 0) / 4) ?> pollos
                </p>
            </div>
            <?php if ($pct >= 100): ?>
            <span class="px-4 py-2 rounded-xl font-black text-sm" style="background-color:#3b0000; color:#ef4444; border:1px solid #ef4444;">
                🚨 AGOTADOS
            </span>
            <?php elseif ($pct >= 80): ?>
            <span class="px-4 py-2 rounded-xl font-black text-sm" style="background-color:#4a0e0e; color:#fca5a5; border:1px solid #ef4444;">
                🔴 Nivel crítico
            </span>
            <?php elseif ($pct >= 50): ?>
            <span class="px-4 py-2 rounded-xl font-black text-sm" style="background-color:#3a2a0f; color:#fbbf24; border:1px solid #f59e0b;">
                ⚠️ Nivel preventivo
            </span>
            <?php else: ?>
            <span class="px-4 py-2 rounded-xl font-black text-sm" style="background-color:#132a1e; color:#4ade80; border:1px solid #16a34a;">
                ✅ Normal
            </span>
            <?php endif; ?>
        </div>

        <form method="POST" action="/config/reset-condimentos">
            <?= Csrf::field() ?>
            <button type="submit"
                    class="w-full font-black text-lg py-4 rounded-xl"
                    style="background-color:#78350f; color:#fbbf24; border:2px solid #f59e0b;"
                    onclick="return confirm('¿Reiniciar el contador? Esto marca el punto de inicio para un nuevo ciclo de condimentos.')">
                🔄 Reiniciar ciclo — Nueva preparación lista
            </button>
        </form>
    </div>

</div>

<!-- Botón regresar -->
<a href="<?= $dashboardUrl ?? '/dashboard' ?>"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg btn-primary">
    ← REGRESAR
</a>

</body>
</html>

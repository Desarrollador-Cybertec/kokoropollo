<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

$items = (isset($items) && is_array($items)) ? $items : [];
$editarItem = (isset($editarItem) && is_array($editarItem)) ? $editarItem : null;
$busqueda = isset($busqueda) ? (string) $busqueda : '';
$dashboardUrl = isset($dashboardUrl) ? (string) $dashboardUrl : '/dashboard';

/**
 * Formatea la cantidad según la categoría.
 * Pollo Crudo se rastrea en cuartos internamente pero se muestra en pollos.
 */
function formatCantidad(int $cuartos, string $categoria): string
{
    if ($categoria !== 'Pollo Crudo') {
        return $cuartos . ' uds';
    }
    $pollos = intdiv($cuartos, 4);
    $resto  = $cuartos % 4;
    $s = $pollos . ($pollos === 1 ? ' pollo' : ' pollos');
    if ($resto > 0) {
        $s .= ' + ' . $resto . ($resto === 1 ? ' cuarto' : ' cuartos');
    }
    return $s;
}

$categorias = [
    'Pollo Crudo'     => ['emoji' => '🐔', 'color' => 'bg-amber-900 text-amber-200'],
    'Acompañamientos' => ['emoji' => '🍌', 'color' => 'bg-lime-900 text-lime-200'],
    'Bebidas'         => ['emoji' => '🥤', 'color' => 'bg-blue-900 text-blue-200'],
    'Otros'           => ['emoji' => '📦', 'color' => 'bg-gray-700 text-gray-200'],
];

$pageTitle = 'Inventario — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-28" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>
<?php require dirname(__DIR__) . '/partials/confirm-modal.php' ?>

<div class="max-w-5xl mx-auto px-4">

    <h1 class="text-4xl font-black text-center mb-8 tracking-wide" style="color:var(--oro);">
        📦 Inventario
    </h1>

    <!-- Formulario agregar / editar -->
    <div class="rounded-2xl shadow-xl p-6 mb-6" style="background-color:var(--rojo-card);">
        <h2 class="text-xl font-black mb-5" style="color:var(--oro);">
            <?= $editarItem ? '✏️ Editar artículo' : '➕ Agregar artículo' ?>
        </h2>
        <form method="POST"
              action="<?= $editarItem ? '/inventario/update' : '/inventario/store' ?>"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <?= Csrf::field() ?>
            <?php if ($editarItem): ?>
                <input type="hidden" name="id" value="<?= (int) $editarItem['id'] ?>">
            <?php endif; ?>

            <!-- Artículo -->
            <div class="lg:col-span-2">
                <label class="block text-sm font-bold mb-1" style="color:var(--oro);">Artículo</label>
                <input type="text" name="articulo" placeholder="Nombre del artículo"
                       value="<?= View::escape($editarItem['articulo'] ?? '') ?>"
                       required maxlength="150"
                       class="w-full text-lg px-4 py-3 rounded-xl input-dark">
            </div>

            <!-- Categoría -->
            <?php
            $categoriaActual = $editarItem['categoria'] ?? 'Otros';
            if (in_array($categoriaActual, ['Pollo', 'Asado', 'Broaster'], true)) {
                $categoriaActual = 'Pollo Crudo';
            }
            if (in_array($categoriaActual, ['Papas', 'Salsas'], true)) {
                $categoriaActual = 'Acompañamientos';
            }
            $esPolloForm     = $categoriaActual === 'Pollo Crudo';
            $cuartosRaw      = (int) ($editarItem['cantidad'] ?? 0);
            $cantidadDisplay = $esPolloForm ? intdiv($cuartosRaw, 4) : $cuartosRaw;
            $valorRaw        = (float) ($editarItem['valor'] ?? 0);
            $valorDisplay    = $esPolloForm ? ($valorRaw * 4) : $valorRaw;
            ?>
            <div>
                <label class="block text-sm font-bold mb-1" style="color:var(--oro);">Categoría</label>
                <select id="selectCategoria" name="categoria" required
                        class="w-full text-lg px-4 py-3 rounded-xl font-semibold input-dark"
                        style="color:var(--oro); appearance:none;">
                    <?php foreach ($categorias as $cat => $cfg): ?>
                        <option value="<?= $cat ?>"
                            <?= $categoriaActual === $cat ? 'selected' : '' ?>
                            style="background-color:var(--rojo-deep); color:var(--oro);">
                            <?= $cfg['emoji'] ?> <?= $cat ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cantidad -->
            <div>
                <label id="labelCantidad" class="block text-sm font-bold mb-1" style="color:var(--oro);">
                    <?= $esPolloForm ? 'Pollos enteros' : 'Cantidad' ?>
                </label>
                <input type="number" id="inputCantidad" name="cantidad" placeholder="0"
                       value="<?= $cantidadDisplay ?>"
                       data-cuartos-raw="<?= $cuartosRaw ?>"
                       required min="0"
                       class="w-full text-lg px-4 py-3 rounded-xl input-dark">
                <p id="notaCantidad" class="text-xs mt-1 font-semibold" style="color:#9ca3af;">
                    <?= $esPolloForm ? '(1 pollo = 4 cuartos)' : '' ?>
                </p>
            </div>

            <!-- Valor -->
            <div>
                <label id="labelValor" class="block text-sm font-bold mb-1" style="color:var(--oro);">
                    <?= $esPolloForm ? 'Costo por pollo $' : 'Valor $' ?>
                </label>
                <input type="number" name="valor" placeholder="0"
                       value="<?= $valorDisplay ?>"
                       required min="0" step="0.01"
                       class="w-full text-lg px-4 py-3 rounded-xl input-dark">
            </div>

            <!-- Botones -->
            <div class="sm:col-span-2 lg:col-span-5 flex gap-3">
                <button type="submit"
                        class="font-black text-lg px-8 py-3 rounded-xl btn-primary">
                    <?= $editarItem ? '💾 Guardar cambios' : '➕ Agregar' ?>
                </button>
                <?php if ($editarItem): ?>
                    <a href="/inventario"
                       class="font-bold text-lg px-8 py-3 rounded-xl text-center btn-secondary">
                        ✕ Cancelar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Buscador -->
    <div class="rounded-2xl shadow-xl p-5 mb-6" style="background-color:var(--rojo-card);">
        <form method="GET" action="/inventario" class="flex gap-4 flex-wrap">
            <input type="text" name="q" placeholder="🔍 Buscar artículo..."
                   value="<?= View::escape($busqueda ?? '') ?>"
                   class="flex-1 min-w-[200px] text-lg px-4 py-3 rounded-xl input-dark">
            <button type="submit"
                    class="font-black text-lg px-6 py-3 rounded-xl btn-primary">
                Buscar
            </button>
            <?php if (!empty($busqueda)): ?>
                <a href="/inventario"
                   class="font-bold text-lg px-6 py-3 rounded-xl text-center btn-secondary">
                    Limpiar
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla -->
    <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color:var(--rojo-card);">
        <div class="overflow-x-auto overflow-y-auto" style="max-height:480px;">
            <table class="w-full text-lg">
                <thead class="sticky top-0">
                    <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                        <th class="px-4 py-4 text-left font-bold">ID</th>
                        <th class="px-4 py-4 text-left font-bold">Artículo</th>
                        <th class="px-4 py-4 text-left font-bold">Categoría</th>
                        <th class="px-4 py-4 text-left font-bold">Cantidad</th>
                        <th class="px-4 py-4 text-left font-bold">Valor</th>
                        <th class="px-4 py-4 text-left font-bold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item):
                    $categoriaItem = (string) ($item['categoria'] ?? 'Otros');
                    if (in_array($categoriaItem, ['Pollo', 'Asado', 'Broaster'], true)) {
                        $categoriaItem = 'Pollo Crudo';
                    }
                    if (in_array($categoriaItem, ['Papas', 'Salsas'], true)) {
                        $categoriaItem = 'Acompañamientos';
                    }
                    $cat = $categorias[$categoriaItem] ?? $categorias['Otros'];
                ?>
                    <tr class="border-b text-white tr-dark" style="border-color:var(--rojo-mid);">
                        <td class="px-4 py-4" style="color:#9ca3af;"><?= (int) $item['id'] ?></td>
                        <td class="px-4 py-4 font-semibold"><?= View::escape($item['articulo']) ?></td>
                        <td class="px-4 py-4">
                            <span class="px-3 py-1 rounded-full text-sm font-bold <?= $cat['color'] ?>">
                                <?= $cat['emoji'] ?> <?= View::escape($categoriaItem) ?>
                            </span>
                        </td>
                        <?php
                        $esPollo  = $categoriaItem === 'Pollo Crudo';
                        $stockBajo = $esPollo
                            ? (int)$item['cantidad'] < 4    // menos de 1 pollo
                            : (int)$item['cantidad'] <= 5;
                        ?>
                        <td class="px-4 py-4">
                            <span class="font-bold <?= $stockBajo ? 'text-red-400' : 'text-green-400' ?>">
                                <?= formatCantidad((int) $item['cantidad'], $categoriaItem) ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 font-semibold" style="color:var(--oro);">
                            <?php $valorTabla = $categoriaItem === 'Pollo Crudo' ? ((float) $item['valor'] * 4) : (float) $item['valor']; ?>
                            $<?= number_format($valorTabla, 0, ',', '.') ?>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex gap-2 flex-wrap">
                                <a href="/inventario?editar=<?= (int) $item['id'] ?>"
                                   class="font-bold px-4 py-2 rounded-lg text-sm btn-primary">
                                    ✏️ Editar
                                </a>
                                <form method="POST" action="/inventario/delete" class="inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="button"
                                            class="font-bold px-4 py-2 rounded-lg text-sm btn-danger"
                                            data-nombre="<?= View::escape($item['articulo']) ?>"
                                            onclick="window.confirmar('¿Eliminar ' + this.dataset.nombre + '?', () => this.closest('form').submit())">
                                        🗑️ Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-10 text-xl" style="color:#9ca3af;">
                            Sin artículos registrados
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Botón regresar -->
<a href="<?= View::escape($dashboardUrl) ?>"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg btn-primary">
    ← REGRESAR
</a>

<script>
(function () {
    const CAT_POLLO_CRUDO = 'Pollo Crudo';
    const sel   = document.getElementById('selectCategoria');
    const input = document.getElementById('inputCantidad');
    const labelValor = document.getElementById('labelValor');
    const label = document.getElementById('labelCantidad');
    const nota  = document.getElementById('notaCantidad');
    if (!sel || !input) return;

    function aplicarModoPollo(esPollo) {
        label.textContent   = esPollo ? 'Pollos enteros' : 'Cantidad';
        if (labelValor) labelValor.textContent = esPollo ? 'Costo por pollo $' : 'Valor $';
        nota.textContent    = esPollo ? '(1 pollo = 4 cuartos)' : '';
        input.dataset.modo  = esPollo ? 'pollos' : 'unidades';
    }

    // Estado inicial ya fijado por PHP; solo actualizamos dataset.modo
    aplicarModoPollo(sel.value === CAT_POLLO_CRUDO);

    // Cambio de categoría
    sel.addEventListener('change', function () {
        const esPollo = this.value === CAT_POLLO_CRUDO;
        input.value   = '';           // limpiar para que el usuario ingrese el nuevo valor
        aplicarModoPollo(esPollo);
    });
})();
</script>

</body>
</html>

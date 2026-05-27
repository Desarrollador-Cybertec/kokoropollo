<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

$categorias = [
    'Asado'           => ['emoji' => '🍗', 'color' => 'bg-red-900 text-red-200'],
    'Broaster'        => ['emoji' => '🍳', 'color' => 'bg-orange-800 text-orange-200'],
    'Papas'           => ['emoji' => '🥔', 'color' => 'bg-yellow-800 text-yellow-200'],
    'Acompañamientos' => ['emoji' => '🍌', 'color' => 'bg-lime-900 text-lime-200'],
    'Salsas'          => ['emoji' => '🫙', 'color' => 'bg-teal-900 text-teal-200'],
    'Bebidas'         => ['emoji' => '🥤', 'color' => 'bg-blue-900 text-blue-200'],
    'Otros'           => ['emoji' => '📦', 'color' => 'bg-gray-700 text-gray-200'],
];

$pageTitle = 'Inventario — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<body style="background-color:var(--rojo-deep);" class="min-h-screen py-8 pb-28">

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
            <div>
                <label class="block text-sm font-bold mb-1" style="color:var(--oro);">Categoría</label>
                <select name="categoria" required
                        class="w-full text-lg px-4 py-3 rounded-xl font-semibold input-dark"
                        style="color:var(--oro); appearance:none;">
                    <?php foreach ($categorias as $cat => $cfg): ?>
                        <option value="<?= $cat ?>"
                            <?= ($editarItem['categoria'] ?? 'Otros') === $cat ? 'selected' : '' ?>
                            style="background-color:var(--rojo-deep); color:var(--oro);">
                            <?= $cfg['emoji'] ?> <?= $cat ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cantidad -->
            <div>
                <label class="block text-sm font-bold mb-1" style="color:var(--oro);">Cantidad</label>
                <input type="number" name="cantidad" placeholder="0"
                       value="<?= (int) ($editarItem['cantidad'] ?? 0) ?>"
                       required min="0"
                       class="w-full text-lg px-4 py-3 rounded-xl input-dark">
            </div>

            <!-- Valor -->
            <div>
                <label class="block text-sm font-bold mb-1" style="color:var(--oro);">Valor $</label>
                <input type="number" name="valor" placeholder="0"
                       value="<?= (float) ($editarItem['valor'] ?? 0) ?>"
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
                    $cat = $categorias[$item['categoria']] ?? $categorias['Otros'];
                ?>
                    <tr class="border-b text-white tr-dark" style="border-color:var(--rojo-mid);">
                        <td class="px-4 py-4" style="color:#9ca3af;"><?= (int) $item['id'] ?></td>
                        <td class="px-4 py-4 font-semibold"><?= View::escape($item['articulo']) ?></td>
                        <td class="px-4 py-4">
                            <span class="px-3 py-1 rounded-full text-sm font-bold <?= $cat['color'] ?>">
                                <?= $cat['emoji'] ?> <?= View::escape($item['categoria']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="font-bold <?= (int)$item['cantidad'] <= 5 ? 'text-red-400' : 'text-green-400' ?>">
                                <?= (int) $item['cantidad'] ?> uds
                            </span>
                        </td>
                        <td class="px-4 py-4 font-semibold" style="color:var(--oro);">
                            $<?= number_format((float) $item['valor'], 0, ',', '.') ?>
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

</body>
</html>

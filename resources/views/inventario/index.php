<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

$items       = (isset($items) && is_array($items)) ? $items : [];
$editarItem  = (isset($editarItem) && is_array($editarItem)) ? $editarItem : null;
$busqueda    = isset($busqueda) ? (string) $busqueda : '';
$dashboardUrl = isset($dashboardUrl) ? (string) $dashboardUrl : '/dashboard';
$soloLectura = (bool) ($soloLectura ?? false);
$movimientos = (isset($movimientos) && is_array($movimientos)) ? $movimientos : [];

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

<!-- Modal entrada / salida -->
<dialog id="modal-movimiento"
        class="rounded-2xl shadow-2xl p-0 border-0 w-full max-w-sm"
        style="background-color:var(--rojo-card); color:white;">
    <div class="p-6">
        <h2 id="modal-titulo" class="text-2xl font-black mb-5" style="color:var(--oro);"></h2>
        <form method="POST" action="/inventario/movimiento">
            <?= Csrf::field() ?>
            <input type="hidden" name="id"   id="modal-id">
            <input type="hidden" name="tipo" id="modal-tipo">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-1" style="color:var(--oro);">
                    Cantidad (<span id="modal-unidad">uds</span>)
                </label>
                <input type="number" name="cantidad" id="modal-cantidad"
                       min="1" required
                       class="w-full text-2xl font-black px-4 py-3 rounded-xl input-dark text-center">
            </div>
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 font-black text-lg px-6 py-3 rounded-xl btn-primary">
                    Confirmar
                </button>
                <button type="button"
                        onclick="document.getElementById('modal-movimiento').close()"
                        class="flex-1 font-bold text-lg px-6 py-3 rounded-xl btn-secondary">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</dialog>

<div class="max-w-5xl mx-auto px-4">

    <h1 class="text-4xl font-black text-center mb-8 tracking-wide" style="color:var(--oro);">
        📦 Inventario
    </h1>

    <?php if (!$soloLectura): ?>
    <!-- Formulario agregar / editar -->
    <div class="rounded-2xl shadow-xl p-6 mb-6" style="background-color:var(--rojo-card);">
        <h2 class="text-xl font-black mb-5" style="color:var(--oro);">
            <?= $editarItem ? '✏️ Editar artículo' : '➕ Agregar artículo' ?>
        </h2>
        <form method="POST"
              action="<?= $editarItem ? '/inventario/update' : '/inventario/store' ?>"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
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
            $esPolloForm  = $categoriaActual === 'Pollo Crudo';
            $valorRaw     = (float) ($editarItem['valor'] ?? 0);
            $valorDisplay = $esPolloForm ? ($valorRaw * 4) : $valorRaw;
            ?>
            <div>
                <label class="block text-sm font-bold mb-1" style="color:var(--oro);">Categoría</label>
                <select id="selectCategoria" name="categoria" required
                        class="w-full text-lg px-4 py-3 rounded-xl font-semibold input-dark"
                        style="color:var(--oro); appearance:none;"
                        <?= $editarItem ? 'disabled' : '' ?>>
                    <?php foreach ($categorias as $cat => $cfg): ?>
                        <option value="<?= $cat ?>"
                            <?= $categoriaActual === $cat ? 'selected' : '' ?>
                            style="background-color:var(--rojo-deep); color:var(--oro);">
                            <?= $cfg['emoji'] ?> <?= $cat ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($editarItem): ?>
                    <!-- Categoría no editable para preservar lógica de cuartos -->
                    <input type="hidden" name="categoria" value="<?= View::escape($categoriaActual) ?>">
                <?php endif; ?>
            </div>

            <!-- Cantidad (solo al crear) -->
            <?php if (!$editarItem): ?>
            <div>
                <label id="labelCantidad" class="block text-sm font-bold mb-1" style="color:var(--oro);">
                    <?= $esPolloForm ? 'Pollos enteros' : 'Cantidad inicial' ?>
                </label>
                <input type="number" id="inputCantidad" name="cantidad" placeholder="0"
                       value="0"
                       required min="0"
                       class="w-full text-lg px-4 py-3 rounded-xl input-dark">
                <p id="notaCantidad" class="text-xs mt-1 font-semibold" style="color:#9ca3af;"></p>
            </div>
            <?php else: ?>
                <!-- Al editar la cantidad se gestiona mediante entradas/salidas -->
                <input type="hidden" name="cantidad" value="<?= (int) ($editarItem['cantidad'] ?? 0) ?>">
            <?php endif; ?>

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
            <div class="sm:col-span-2 lg:col-span-4 flex gap-3">
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
    <?php endif; ?>

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
        <div class="overflow-x-auto overflow-y-auto" style="max-height:600px;">
            <table class="w-full text-lg">
                <thead class="sticky top-0">
                    <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                        <th class="px-4 py-4 text-left font-bold">Artículo</th>
                        <th class="px-4 py-4 text-left font-bold">Categoría</th>
                        <th class="px-4 py-4 text-left font-bold">Stock</th>
                        <th class="px-4 py-4 text-left font-bold">Valor</th>
                        <?php if (!$soloLectura): ?>
                        <th class="px-4 py-4 text-left font-bold">Acciones</th>
                        <?php endif; ?>
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
                    $cat       = $categorias[$categoriaItem] ?? $categorias['Otros'];
                    $esPollo   = $categoriaItem === 'Pollo Crudo';
                    $stockBajo = $esPollo
                        ? (int)$item['cantidad'] < 4
                        : (int)$item['cantidad'] <= 5;
                    $itemId    = (int) $item['id'];
                    $movs      = $movimientos[$itemId] ?? [];
                    $unidad    = $esPollo ? 'pollos' : 'uds';
                ?>
                    <!-- Fila principal -->
                    <tr class="border-b text-white tr-dark" style="border-color:var(--rojo-mid);">
                        <td class="px-4 py-4 font-semibold"><?= View::escape($item['articulo']) ?></td>
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-bold whitespace-nowrap <?= $cat['color'] ?>">
                                <?= $cat['emoji'] ?> <?= View::escape($categoriaItem) ?>
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="font-bold <?= $stockBajo ? 'text-red-400' : 'text-green-400' ?>">
                                <?= formatCantidad((int) $item['cantidad'], $categoriaItem) ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 font-semibold" style="color:var(--oro);">
                            <?php $valorTabla = $esPollo ? ((float) $item['valor'] * 4) : (float) $item['valor']; ?>
                            $<?= number_format($valorTabla, 0, ',', '.') ?>
                        </td>
                        <?php if (!$soloLectura): ?>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1">
                                <!-- Fila 1: movimientos -->
                                <div class="flex gap-1">
                                    <button type="button"
                                            class="font-bold px-3 py-2 rounded-lg text-sm whitespace-nowrap"
                                            style="background-color:#166534; color:#bbf7d0;"
                                            onclick="abrirMovimiento(<?= $itemId ?>, 'entrada', <?= htmlspecialchars(json_encode($item['articulo']), ENT_QUOTES) ?>, <?= $esPollo ? 'true' : 'false' ?>)">
                                        ➕ Entrada
                                    </button>
                                    <button type="button"
                                            class="font-bold px-3 py-2 rounded-lg text-sm whitespace-nowrap"
                                            style="background-color:#7c2d12; color:#fed7aa;"
                                            onclick="abrirMovimiento(<?= $itemId ?>, 'salida', <?= htmlspecialchars(json_encode($item['articulo']), ENT_QUOTES) ?>, <?= $esPollo ? 'true' : 'false' ?>)">
                                        ➖ Salida
                                    </button>
                                </div>
                                <!-- Fila 2: gestión + historial -->
                                <div class="flex gap-1 items-center">
                                    <a href="/inventario?editar=<?= $itemId ?>"
                                       class="font-bold px-3 py-1 rounded-lg text-sm btn-primary whitespace-nowrap">
                                        ✏️ Editar
                                    </a>
                                    <form method="POST" action="/inventario/delete" class="inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= $itemId ?>">
                                        <button type="button"
                                                class="font-bold px-3 py-1 rounded-lg text-sm btn-danger"
                                                data-nombre="<?= View::escape($item['articulo']) ?>"
                                                onclick="window.confirmar('¿Eliminar ' + this.dataset.nombre + '?', () => this.closest('form').submit())">
                                            🗑️
                                        </button>
                                    </form>
                                    <?php if (!empty($movs)): ?>
                                    <button type="button"
                                            class="font-bold px-3 py-1 rounded-lg text-sm whitespace-nowrap"
                                            style="background-color:#1e3a5f; color:#bae6fd;"
                                            onclick="toggleHist(<?= $itemId ?>)">
                                        📋 Historial
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>

                    <!-- Fila historial (oculta por defecto) -->
                    <?php if (!empty($movs)): ?>
                    <tr id="hist-<?= $itemId ?>" class="hidden">
                        <td colspan="<?= $soloLectura ? 4 : 5 ?>"
                            style="background-color:var(--rojo-deep); padding:0 1rem 1rem;">
                            <p class="text-sm font-bold pt-3 pb-2" style="color:var(--oro);">
                                Últimos movimientos — <?= View::escape($item['articulo']) ?>
                            </p>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr style="color:#9ca3af;">
                                        <th class="text-left py-1 pr-4">Tipo</th>
                                        <th class="text-left py-1 pr-4">Cantidad</th>
                                        <th class="text-left py-1 pr-4">Usuario</th>
                                        <th class="text-left py-1">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($movs as $mov): ?>
                                    <tr class="border-t" style="border-color:#3f1a1a;">
                                        <td class="py-1 pr-4">
                                            <?php if ($mov['tipo'] === 'entrada'): ?>
                                                <span class="font-bold" style="color:#86efac;">⬆ Entrada</span>
                                            <?php else: ?>
                                                <span class="font-bold" style="color:#fca5a5;">⬇ Salida</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-1 pr-4 font-semibold text-white">
                                            <?= (int) $mov['cantidad'] ?> <?= $unidad ?>
                                        </td>
                                        <td class="py-1 pr-4" style="color:#d1d5db;">
                                            <?= View::escape($mov['usuario']) ?>
                                        </td>
                                        <td class="py-1" style="color:#9ca3af;">
                                            <?= date('d/m/Y H:i', strtotime($mov['creado'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>

                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-10 text-xl" style="color:#9ca3af;">
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
    // Cambio de categoría en formulario de creación
    const sel   = document.getElementById('selectCategoria');
    const input = document.getElementById('inputCantidad');
    const labelValor = document.getElementById('labelValor');
    const label = document.getElementById('labelCantidad');
    const nota  = document.getElementById('notaCantidad');
    if (sel && input) {
        const CAT_POLLO = 'Pollo Crudo';
        function aplicarModo(esPollo) {
            if (label)      label.textContent     = esPollo ? 'Pollos enteros' : 'Cantidad inicial';
            if (labelValor) labelValor.textContent = esPollo ? 'Costo por pollo $' : 'Valor $';
            if (nota)       nota.textContent       = esPollo ? '(1 pollo = 4 cuartos)' : '';
        }
        aplicarModo(sel.value === CAT_POLLO);
        sel.addEventListener('change', function () {
            input.value = '';
            aplicarModo(this.value === CAT_POLLO);
        });
    }
})();

function abrirMovimiento(id, tipo, articulo, esPollo) {
    document.getElementById('modal-id').value    = id;
    document.getElementById('modal-tipo').value  = tipo;
    document.getElementById('modal-cantidad').value = '';
    const icono = tipo === 'entrada' ? '➕ Entrada' : '➖ Salida';
    document.getElementById('modal-titulo').textContent = icono + ' — ' + articulo;
    document.getElementById('modal-unidad').textContent = esPollo ? 'pollos' : 'uds';
    document.getElementById('modal-movimiento').showModal();
}

function toggleHist(id) {
    const row = document.getElementById('hist-' + id);
    if (row) row.classList.toggle('hidden');
}
</script>

</body>
</html>

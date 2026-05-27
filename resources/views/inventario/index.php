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
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario — Kokoro Pollo</title>
    <?= Csrf::meta() ?>
    <link rel="stylesheet" href="/css/styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body style="background-color:#2b1a1a;" class="min-h-screen py-8 pb-28">

<div class="max-w-5xl mx-auto px-4">

    <h1 class="text-4xl font-black text-center mb-8 tracking-wide" style="color:#d4af37;">
        📦 Inventario
    </h1>

    <!-- Mensajes flash -->
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 text-red-800 text-lg font-semibold px-5 py-4 rounded-xl mb-6">
            <?= View::escape($error) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($exito)): ?>
        <div class="bg-green-100 text-green-800 text-lg font-semibold px-5 py-4 rounded-xl mb-6">
            <?= View::escape($exito) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario agregar / editar -->
    <div class="rounded-2xl shadow-xl p-6 mb-6" style="background-color:#3c1f1f;">
        <h2 class="text-xl font-black mb-5" style="color:#d4af37;">
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
                <label class="block text-sm font-bold mb-1" style="color:#d4af37;">Artículo</label>
                <input type="text" name="articulo" placeholder="Nombre del artículo"
                       value="<?= View::escape($editarItem['articulo'] ?? '') ?>"
                       required maxlength="150"
                       class="w-full text-white text-lg px-4 py-3 rounded-xl border outline-none"
                       style="background-color:#2b1a1a; border-color:#5a1a1a; color:white;"
                       onfocus="this.style.borderColor='#d4af37'"
                       onblur="this.style.borderColor='#5a1a1a'">
            </div>

            <!-- Categoría -->
            <div>
                <label class="block text-sm font-bold mb-1" style="color:#d4af37;">Categoría</label>
                <select name="categoria" required
                        class="w-full text-lg px-4 py-3 rounded-xl border outline-none font-semibold"
                        style="background-color:#2b1a1a; border-color:#5a1a1a; color:#d4af37; appearance:none;"
                        onfocus="this.style.borderColor='#d4af37'"
                        onblur="this.style.borderColor='#5a1a1a'">
                    <?php foreach ($categorias as $cat => $cfg): ?>
                        <option value="<?= $cat ?>"
                            <?= ($editarItem['categoria'] ?? 'Otros') === $cat ? 'selected' : '' ?>
                            style="background-color:#2b1a1a; color:#d4af37;">
                            <?= $cfg['emoji'] ?> <?= $cat ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cantidad -->
            <div>
                <label class="block text-sm font-bold mb-1" style="color:#d4af37;">Cantidad</label>
                <input type="number" name="cantidad" placeholder="0"
                       value="<?= (int) ($editarItem['cantidad'] ?? 0) ?>"
                       required min="0"
                       class="w-full text-white text-lg px-4 py-3 rounded-xl border outline-none"
                       style="background-color:#2b1a1a; border-color:#5a1a1a;"
                       onfocus="this.style.borderColor='#d4af37'"
                       onblur="this.style.borderColor='#5a1a1a'">
            </div>

            <!-- Valor -->
            <div>
                <label class="block text-sm font-bold mb-1" style="color:#d4af37;">Valor $</label>
                <input type="number" name="valor" placeholder="0"
                       value="<?= (float) ($editarItem['valor'] ?? 0) ?>"
                       required min="0" step="0.01"
                       class="w-full text-white text-lg px-4 py-3 rounded-xl border outline-none"
                       style="background-color:#2b1a1a; border-color:#5a1a1a;"
                       onfocus="this.style.borderColor='#d4af37'"
                       onblur="this.style.borderColor='#5a1a1a'">
            </div>

            <!-- Botones -->
            <div class="sm:col-span-2 lg:col-span-5 flex gap-3">
                <button type="submit"
                        class="font-black text-lg px-8 py-3 rounded-xl transition-all"
                        style="background-color:#d4af37; color:#3b0a0a;"
                        onmouseover="this.style.backgroundColor='#e6c857'"
                        onmouseout="this.style.backgroundColor='#d4af37'">
                    <?= $editarItem ? '💾 Guardar cambios' : '➕ Agregar' ?>
                </button>
                <?php if ($editarItem): ?>
                    <a href="/inventario"
                       class="font-bold text-lg px-8 py-3 rounded-xl transition-all text-center"
                       style="background-color:#5a1a1a; color:white;"
                       onmouseover="this.style.backgroundColor='#4a0e0e'"
                       onmouseout="this.style.backgroundColor='#5a1a1a'">
                        ✕ Cancelar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Buscador -->
    <div class="rounded-2xl shadow-xl p-5 mb-6" style="background-color:#3c1f1f;">
        <form method="GET" action="/inventario" class="flex gap-4 flex-wrap">
            <input type="text" name="q" placeholder="🔍 Buscar artículo..."
                   value="<?= View::escape($busqueda ?? '') ?>"
                   class="flex-1 min-w-[200px] text-white text-lg px-4 py-3 rounded-xl border outline-none"
                   style="background-color:#2b1a1a; border-color:#5a1a1a;"
                   onfocus="this.style.borderColor='#d4af37'"
                   onblur="this.style.borderColor='#5a1a1a'">
            <button type="submit"
                    class="font-black text-lg px-6 py-3 rounded-xl transition-all"
                    style="background-color:#d4af37; color:#3b0a0a;"
                    onmouseover="this.style.backgroundColor='#e6c857'"
                    onmouseout="this.style.backgroundColor='#d4af37'">
                Buscar
            </button>
            <?php if (!empty($busqueda)): ?>
                <a href="/inventario"
                   class="font-bold text-lg px-6 py-3 rounded-xl transition-all text-center"
                   style="background-color:#5a1a1a; color:white;">
                    Limpiar
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla -->
    <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color:#3c1f1f;">
        <div class="overflow-x-auto overflow-y-auto" style="max-height:480px;">
            <table class="w-full text-lg">
                <thead class="sticky top-0">
                    <tr style="background-color:#5a1a1a; color:#d4af37;">
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
                    <tr class="border-b text-white transition-colors"
                        style="border-color:#5a1a1a;"
                        onmouseover="this.style.backgroundColor='#4a0e0e'"
                        onmouseout="this.style.backgroundColor='transparent'">
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
                        <td class="px-4 py-4 font-semibold" style="color:#d4af37;">
                            $<?= number_format((float) $item['valor'], 0, ',', '.') ?>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex gap-2 flex-wrap">
                                <a href="/inventario?editar=<?= (int) $item['id'] ?>"
                                   class="font-bold px-4 py-2 rounded-lg transition-all text-sm"
                                   style="background-color:#d4af37; color:#3b0a0a;"
                                   onmouseover="this.style.backgroundColor='#e6c857'"
                                   onmouseout="this.style.backgroundColor='#d4af37'">
                                    ✏️ Editar
                                </a>
                                <form method="POST" action="/inventario/delete" class="inline"
                                      onsubmit="return confirm('¿Eliminar <?= View::escape(addslashes($item['articulo'])) ?>?')">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit"
                                            class="font-bold px-4 py-2 rounded-lg transition-all text-sm"
                                            style="background-color:#b91c1c; color:white;"
                                            onmouseover="this.style.backgroundColor='#ef4444'"
                                            onmouseout="this.style.backgroundColor='#b91c1c'">
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
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg transition-all"
   style="background-color:#d4af37; color:#3b0a0a;"
   onmouseover="this.style.backgroundColor='#e6c857'"
   onmouseout="this.style.backgroundColor='#d4af37'">
    ← REGRESAR
</a>

</body>
</html>

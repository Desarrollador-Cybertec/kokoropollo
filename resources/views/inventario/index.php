<?php

declare(strict_types=1);

use App\Core\{Csrf, View};

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario — Kokoro Pollo</title>
    <?= Csrf::meta() ?>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="page-module">

<div class="container">
    <h1>Inventario</h1>

    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?= View::escape($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($exito)): ?>
        <div class="flash flash-success"><?= View::escape($exito) ?></div>
    <?php endif; ?>

    <!-- Formulario agregar / editar -->
    <form class="formulario" method="POST"
          action="<?= $editarItem ? '/inventario/update' : '/inventario/store' ?>">
        <?= Csrf::field() ?>
        <?php if ($editarItem): ?>
            <input type="hidden" name="id" value="<?= (int) $editarItem['id'] ?>">
        <?php endif; ?>
        <input type="text"   name="articulo" placeholder="Artículo"
               value="<?= View::escape($editarItem['articulo'] ?? '') ?>" required maxlength="150">
        <input type="number" name="cantidad"  placeholder="Cantidad"
               value="<?= (int) ($editarItem['cantidad'] ?? 0) ?>" required min="0">
        <input type="number" name="valor"     placeholder="Valor"
               value="<?= (float) ($editarItem['valor'] ?? 0) ?>" required min="0" step="0.01">
        <button type="submit" class="btn-primary">
            <?= $editarItem ? 'Guardar cambios' : 'Agregar' ?>
        </button>
        <?php if ($editarItem): ?>
            <a href="/inventario" class="btn-primary" style="text-decoration:none;">Cancelar</a>
        <?php endif; ?>
    </form>

    <!-- Buscador -->
    <form method="GET" action="/inventario" class="buscar-form">
        <input type="text" name="q" placeholder="Buscar artículo..."
               value="<?= View::escape($busqueda ?? '') ?>">
        <button type="submit" class="btn-primary">Buscar</button>
        <?php if (!empty($busqueda)): ?>
            <a href="/inventario" class="btn-primary" style="text-decoration:none;">Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- Tabla -->
    <div class="tabla-con-scroll">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Artículo</th>
                    <th>Cantidad</th>
                    <th>Valor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= (int) $item['id'] ?></td>
                    <td><?= View::escape($item['articulo']) ?></td>
                    <td><?= (int) $item['cantidad'] ?></td>
                    <td>$<?= number_format((float) $item['valor'], 0, ',', '.') ?></td>
                    <td>
                        <a class="btn-accion btn-editar"
                           href="/inventario?editar=<?= (int) $item['id'] ?>">✏️ Editar</a>
                        <form method="POST" action="/inventario/delete"
                              style="display:inline;"
                              onsubmit="return confirm('¿Eliminar <?= View::escape($item['articulo']) ?>?')">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <button type="submit" class="btn-accion btn-eliminar">🗑️ Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
                <tr><td colspan="5" style="text-align:center;color:#aaa;">Sin artículos</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<a class="volver-btn" href="<?= View::escape($dashboardUrl) ?>">← REGRESAR</a>

</body>
</html>

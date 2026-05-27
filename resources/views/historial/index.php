<?php

declare(strict_types=1);

use App\Core\{Csrf, View};

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Caja — Kokoro Pollo</title>
    <?= Csrf::meta() ?>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="page-historial">

<div class="historial-container">
    <h1 style="text-align:center;color:#ffcc00;margin-bottom:30px;font-size:2em;text-shadow:2px 2px 4px #000;">
        Historial de Movimientos de Caja
    </h1>

    <form method="GET" action="/historial" class="filtro-form">
        <label>Desde:</label>
        <input type="date" name="desde" value="<?= View::escape($desde ?? '') ?>">
        <label>Hasta:</label>
        <input type="date" name="hasta" value="<?= View::escape($hasta ?? '') ?>">
        <button type="submit" class="btn-primary">Filtrar</button>
        <?php if (!empty($desde) || !empty($hasta)): ?>
            <a href="/historial" class="btn-primary" style="text-decoration:none;">Limpiar</a>
        <?php endif; ?>
    </form>

    <table class="historial-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Concepto</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($registros as $row): ?>
            <tr>
                <td><?= (int) $row['id'] ?></td>
                <td><?= View::escape($row['fecha']) ?></td>
                <td><?= ucfirst(View::escape($row['tipo'])) ?></td>
                <td>$<?= number_format((float) $row['valor'], 0, ',', '.') ?></td>
                <td><?= View::escape($row['concepto']) ?></td>
                <td><?= View::escape($row['usuario']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($registros)): ?>
            <tr><td colspan="6" style="text-align:center;padding:20px;">Sin registros</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<a class="volver-btn" href="/caja">← REGRESAR</a>

</body>
</html>

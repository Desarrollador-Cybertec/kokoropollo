<?php

declare(strict_types=1);

use App\Core\{Csrf, View};

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja — Kokoro Pollo</title>
    <?= Csrf::meta() ?>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="page-caja">

<div class="caja-container">
    <h1>TOTAL EN CAJA</h1>

    <input type="text" class="readonly-box"
           value="$<?= number_format((float) $total, 2, ',', '.') ?>" readonly>

    <?php if (!empty($error)): ?>
        <div class="error-box"><?= View::escape($error) ?></div>
    <?php endif; ?>

    <div class="acciones">
        <div class="bloque">
            <h2>AÑADIR DINERO A CAJA</h2>
            <form method="POST" action="/caja">
                <?= Csrf::field() ?>
                <input type="hidden" name="accion" value="anadir">
                <input type="number" step="0.01" name="valor"
                       class="input-caja" placeholder="Ingrese valor" required min="0.01">
                <input type="text" name="concepto"
                       class="input-caja" placeholder="Concepto" required maxlength="255">
                <button type="submit" class="btn-caja">AÑADIR</button>
            </form>
        </div>

        <div class="bloque">
            <h2>RETIRAR DINERO DE CAJA</h2>
            <form method="POST" action="/caja">
                <?= Csrf::field() ?>
                <input type="hidden" name="accion" value="retirar">
                <input type="number" step="0.01" name="valor"
                       class="input-caja" placeholder="Ingrese valor" required min="0.01">
                <input type="text" name="concepto"
                       class="input-caja" placeholder="Concepto" required maxlength="255">
                <button type="submit" class="btn-caja">RETIRAR</button>
            </form>
        </div>
    </div>

    <div class="historial-link-wrap" style="margin-top:30px;">
        <a class="btn-historial" href="/historial">Ver Historial</a>
    </div>
</div>

<a class="volver-btn" href="<?= View::escape($dashboardUrl) ?>">← REGRESAR</a>

</body>
</html>

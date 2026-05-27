<?php
declare(strict_types=1);
use App\Core\Csrf;
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Kokoro Pollo', ENT_QUOTES, 'UTF-8') ?></title>
    <?= Csrf::meta() ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        :root {
            --rojo-deep:  #2b1a1a;
            --rojo-card:  #3c1f1f;
            --rojo-dark:  #3b0a0a;
            --rojo-mid:   #5a1a1a;
            --rojo-hover: #4a0e0e;
            --oro:        #d4af37;
            --oro-light:  #e6c857;
        }
    </style>
    <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>

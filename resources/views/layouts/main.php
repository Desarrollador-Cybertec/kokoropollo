<?php

declare(strict_types=1);

use App\Core\{Csrf, Session, View};

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::escape($title ?? 'Kokoro Pollo') ?></title>
    <?= Csrf::meta() ?>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
    <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body class="<?= View::escape($bodyClass ?? '') ?>">

<?php if ($flash = Session::getFlash('success')): ?>
    <div class="flash flash-success"><?= View::escape($flash) ?></div>
<?php endif; ?>
<?php if ($flash = Session::getFlash('error')): ?>
    <div class="flash flash-error"><?= View::escape($flash) ?></div>
<?php endif; ?>

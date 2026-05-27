<?php

declare(strict_types=1);

use App\Core\{Csrf, View};

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Kokoro Pollo</title>
    <?= Csrf::meta() ?>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
</head>
<body class="page-login">

<div class="login-container">
    <div class="logo-container">
        <img src="/img/logo.png" alt="Logo Asadero Kokoro Pollo">
    </div>

    <h2>INICIAR SESIÓN</h2>

    <?php if (!empty($error)): ?>
        <div class="flash flash-error"><?= View::escape($error) ?></div>
    <?php endif; ?>

    <form action="/login" method="POST">
        <?= Csrf::field() ?>
        <div class="input-group">
            <i class="fa fa-user"></i>
            <input type="text" name="usuario" placeholder="Usuario" required autocomplete="username">
        </div>
        <div class="input-group">
            <i class="fa fa-lock"></i>
            <input type="password" name="contrasena" placeholder="Contraseña" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-login">
            <i class="fa fa-sign-in-alt"></i> INGRESAR
        </button>
    </form>

    <p class="footer-login">© Asadero Kokoro Pollo</p>
</div>

</body>
</html>

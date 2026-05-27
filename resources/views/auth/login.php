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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
</head>
<body class="min-h-screen flex items-center justify-center p-4"
      style="background-color:#3b0a0a;">

<div class="w-full max-w-md rounded-2xl shadow-2xl p-10 text-center"
     style="background-color:rgba(0,0,0,0.45);">

    <!-- Logo -->
    <div class="flex justify-center mb-6">
        <img src="/img/logo.png" alt="Logo Kokoro Pollo" class="w-44">
    </div>

    <!-- Título -->
    <h1 class="text-3xl font-black tracking-widest mb-8 uppercase" style="color:#d4af37;">
        Iniciar Sesión
    </h1>

    <!-- Error -->
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 text-red-800 text-lg font-semibold px-4 py-3 rounded-xl mb-6">
            <?= View::escape($error) ?>
        </div>
    <?php endif; ?>

    <form action="/login" method="POST" class="space-y-5">
        <?= Csrf::field() ?>

        <!-- Campo usuario -->
        <div class="flex items-center rounded-xl px-4 py-4 gap-4 border transition-all"
             style="background-color:#1a1a1a; border-color:#5a1a1a;"
             onfocus-within="this.style.borderColor='#d4af37'"
             onmouseover="this.style.borderColor='#d4af37'"
             onmouseout="this.style.borderColor='#5a1a1a'">
            <i class="fa fa-user text-xl w-6" style="color:#d4af37;"></i>
            <input type="text" name="usuario" placeholder="Usuario"
                   required autocomplete="username"
                   class="bg-transparent flex-1 text-white text-xl outline-none placeholder:text-gray-500"
                   onfocus="this.parentElement.style.borderColor='#d4af37'"
                   onblur="this.parentElement.style.borderColor='#5a1a1a'">
        </div>

        <!-- Campo contraseña -->
        <div class="flex items-center rounded-xl px-4 py-4 gap-4 border transition-all"
             style="background-color:#1a1a1a; border-color:#5a1a1a;">
            <i class="fa fa-lock text-xl w-6" style="color:#d4af37;"></i>
            <input type="password" name="contrasena" placeholder="Contraseña"
                   required autocomplete="current-password"
                   class="bg-transparent flex-1 text-white text-xl outline-none placeholder:text-gray-500"
                   onfocus="this.parentElement.style.borderColor='#d4af37'"
                   onblur="this.parentElement.style.borderColor='#5a1a1a'">
        </div>

        <!-- Botón ingresar -->
        <button type="submit"
                class="w-full font-black text-xl py-4 rounded-xl flex items-center justify-center gap-3 transition-all mt-2 uppercase tracking-wide"
                style="background-color:#d4af37; color:#3b0a0a;"
                onmouseover="this.style.backgroundColor='#e6c857'"
                onmouseout="this.style.backgroundColor='#d4af37'">
            <i class="fa fa-sign-in-alt"></i> INGRESAR
        </button>
    </form>

    <p class="text-base mt-8" style="color:#d4af37; opacity:0.6;">© Asadero Kokoro Pollo</p>
</div>

</body>
</html>

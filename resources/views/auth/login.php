<?php
declare(strict_types=1);
use App\Core\Csrf;

$pageTitle = 'Iniciar Sesión — Kokoro Pollo';
$extraHead = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">';
require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen flex items-center justify-center p-4"
      style="background-color:#3b0a0a;">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="w-full max-w-md rounded-2xl shadow-2xl p-10 text-center"
     style="background-color:rgba(0,0,0,0.45);">

    <div class="flex justify-center mb-6">
        <img src="/img/logo.png" alt="Logo Kokoro Pollo" class="w-44">
    </div>

    <h1 class="text-3xl font-black tracking-widest mb-8 uppercase" style="color:#d4af37;">
        Iniciar Sesión
    </h1>

    <form action="/login" method="POST" class="space-y-5">
        <?= Csrf::field() ?>

        <div class="field-dark flex items-center rounded-xl px-4 py-4 gap-4">
            <i class="fa fa-user text-xl w-6" style="color:#d4af37;"></i>
            <input type="text" name="usuario" placeholder="Usuario"
                   required autocomplete="username"
                   class="bg-transparent flex-1 text-white text-xl outline-none placeholder:text-gray-500">
        </div>

        <div class="field-dark flex items-center rounded-xl px-4 py-4 gap-4">
            <i class="fa fa-lock text-xl w-6" style="color:#d4af37;"></i>
            <input type="password" name="contrasena" placeholder="Contraseña"
                   required autocomplete="current-password"
                   class="bg-transparent flex-1 text-white text-xl outline-none placeholder:text-gray-500">
        </div>

        <button type="submit"
                class="w-full font-black text-xl py-4 rounded-xl flex items-center justify-center gap-3 mt-2 uppercase tracking-wide btn-primary">
            <i class="fa fa-sign-in-alt"></i> INGRESAR
        </button>
    </form>

    <p class="text-base mt-8" style="color:#d4af37; opacity:0.6;">© Asadero Kokoro Pollo</p>
</div>

</body>
</html>

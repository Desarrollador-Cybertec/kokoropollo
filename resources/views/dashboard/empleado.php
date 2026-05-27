<?php
declare(strict_types=1);
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Empleado — Kokoro Pollo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: { extend: { colors: {
            rojo:  { 950:'#1a0505', 900:'#2b1a1a', 800:'#3b0a0a', 700:'#3c1f1f', 600:'#4a0e0e', 500:'#5a1a1a' },
            oro:   { DEFAULT:'#d4af37', claro:'#e6c857', oscuro:'#3b0a0a' }
        }}}
    }
    </script>
</head>
<body class="min-h-screen flex flex-col" style="background: linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%)">

    <!-- Header -->
    <header class="flex justify-between items-center px-6 py-5 border-b border-rojo-500">
        <h1 class="text-oro text-3xl md:text-4xl font-black tracking-widest uppercase">KOKORO POLLO</h1>
        <img src="/img/logo.png" alt="Logo" class="h-16 md:h-20 w-auto">
    </header>

    <!-- Main -->
    <main class="flex-1 flex flex-col items-center justify-center p-6 gap-8">
        <p class="text-oro text-2xl md:text-3xl font-bold tracking-widest uppercase">Panel de Empleado</p>

        <div class="bg-rojo-600/80 rounded-2xl shadow-2xl p-8 w-full max-w-xl">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

                <a href="/inventario"
                   class="flex flex-col items-center gap-3 bg-oro hover:bg-oro-claro text-rojo-800 font-black text-xl rounded-2xl py-8 px-4 shadow-lg transition-all hover:scale-105 active:scale-95">
                    <span class="text-5xl">📦</span>
                    INVENTARIO
                </a>

                <a href="/caja"
                   class="flex flex-col items-center gap-3 bg-oro hover:bg-oro-claro text-rojo-800 font-black text-xl rounded-2xl py-8 px-4 shadow-lg transition-all hover:scale-105 active:scale-95">
                    <span class="text-5xl">💰</span>
                    CAJA
                </a>

                <a href="/ventas"
                   class="flex flex-col items-center gap-3 bg-oro hover:bg-oro-claro text-rojo-800 font-black text-xl rounded-2xl py-8 px-4 shadow-lg transition-all hover:scale-105 active:scale-95">
                    <span class="text-5xl">🛒</span>
                    VENTAS
                </a>

            </div>
        </div>
    </main>

    <!-- Logout -->
    <a href="/logout"
       class="fixed bottom-5 right-5 border-2 border-oro text-oro hover:bg-oro hover:text-rojo-800 font-bold text-lg px-5 py-3 rounded-xl transition-all">
        Cerrar sesión
    </a>

</body>
</html>

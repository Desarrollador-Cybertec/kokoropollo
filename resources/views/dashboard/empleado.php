<?php

declare(strict_types=1);

use App\Core\View;

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Empleado — Kokoro Pollo</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">
    <script src="https://unpkg.com/lucide@latest" defer></script>
</head>
<body class="page-dashboard">

    <div class="header">
        <h1>KOKOROPOLLO</h1>
        <img src="/img/logo.png" alt="Logo Asadero Kokoro Pollo">
    </div>

    <div class="main-content">
        <p class="subtitle">PANEL DE EMPLEADO</p>

        <div class="panel-botones">
            <div class="button-group">
                <a href="/inventario">
                    <i data-lucide="boxes"></i>
                    INVENTARIO
                </a>
                <a href="/caja">
                    <i data-lucide="dollar-sign"></i>
                    CAJA
                </a>
                <a href="/ventas">
                    <i data-lucide="bar-chart-3"></i>
                    VENTAS
                </a>
            </div>
        </div>
    </div>

    <a class="logout" href="/logout">Cerrar sesión</a>

    <script>lucide.createIcons();</script>
</body>
</html>

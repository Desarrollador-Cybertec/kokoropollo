<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

$totalIngresos = 0.0;
$totalRetiros  = 0.0;
foreach ($registros as $r) {
    if ($r['tipo'] === 'ingreso') $totalIngresos += (float) $r['valor'];
    else                          $totalRetiros  += (float) $r['valor'];
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Caja — Kokoro Pollo</title>
    <?= Csrf::meta() ?>
    <script src="https://cdn.tailwindcss.com"></script>
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
</head>
<body style="background-color:var(--rojo-deep);" class="min-h-screen py-8 pb-28">

<div class="max-w-5xl mx-auto px-4">

    <h1 class="text-4xl font-black text-center mb-8 tracking-wide" style="color:var(--oro);">
        📋 Historial de Movimientos
    </h1>

    <!-- Filtro de fechas -->
    <div class="rounded-2xl shadow-xl p-5 mb-5" style="background-color:var(--rojo-card);">
        <form method="GET" action="/historial"
              class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-sm font-bold mb-1" style="color:var(--oro);">Desde</label>
                <input type="date" name="desde"
                       value="<?= View::escape($desde ?? '') ?>"
                       class="text-lg font-semibold px-4 py-3 rounded-xl outline-none"
                       style="background-color:var(--rojo-deep); border:2px solid var(--rojo-mid); color:white;"
                       onfocus="this.style.borderColor='var(--oro)'"
                       onblur="this.style.borderColor='var(--rojo-mid)'">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1" style="color:var(--oro);">Hasta</label>
                <input type="date" name="hasta"
                       value="<?= View::escape($hasta ?? '') ?>"
                       class="text-lg font-semibold px-4 py-3 rounded-xl outline-none"
                       style="background-color:var(--rojo-deep); border:2px solid var(--rojo-mid); color:white;"
                       onfocus="this.style.borderColor='var(--oro)'"
                       onblur="this.style.borderColor='var(--rojo-mid)'">
            </div>
            <div class="flex items-end gap-2 mt-auto">
                <button type="submit"
                        class="font-black text-lg px-6 py-3 rounded-xl transition-all"
                        style="background-color:var(--oro); color:var(--rojo-dark);"
                        onmouseover="this.style.backgroundColor='var(--oro-light)'"
                        onmouseout="this.style.backgroundColor='var(--oro)'">
                    Filtrar
                </button>
                <?php if (!empty($desde) || !empty($hasta)): ?>
                    <a href="/historial"
                       class="font-bold text-lg px-6 py-3 rounded-xl transition-all text-center"
                       style="background-color:var(--rojo-mid); color:white;"
                       onmouseover="this.style.backgroundColor='var(--rojo-hover)'"
                       onmouseout="this.style.backgroundColor='var(--rojo-mid)'">
                        Limpiar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Resumen del período -->
    <?php if (!empty($registros)): ?>
    <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="rounded-xl px-4 py-3 text-center" style="background-color:#134e2a;">
            <div class="text-xs font-bold uppercase tracking-wider text-green-300 mb-1">Ingresos</div>
            <div class="text-xl font-black text-green-300">
                +$<?= number_format($totalIngresos, 0, ',', '.') ?>
            </div>
        </div>
        <div class="rounded-xl px-4 py-3 text-center" style="background-color:#4a0e0e;">
            <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#fca5a5;">Retiros</div>
            <div class="text-xl font-black" style="color:#fca5a5;">
                -$<?= number_format($totalRetiros, 0, ',', '.') ?>
            </div>
        </div>
        <div class="rounded-xl px-4 py-3 text-center" style="background-color:var(--rojo-card); border:1px solid var(--rojo-mid);">
            <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:var(--oro);">Neto</div>
            <div class="text-xl font-black" style="color:var(--oro);">
                $<?= number_format($totalIngresos - $totalRetiros, 0, ',', '.') ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabla -->
    <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color:var(--rojo-card);">
        <div class="overflow-x-auto" style="max-height:520px; overflow-y:auto;">
            <table class="w-full text-base">
                <thead class="sticky top-0">
                    <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                        <th class="px-4 py-4 text-left font-bold">ID</th>
                        <th class="px-4 py-4 text-left font-bold">Fecha y hora</th>
                        <th class="px-4 py-4 text-left font-bold">Tipo</th>
                        <th class="px-4 py-4 text-left font-bold">Valor</th>
                        <th class="px-4 py-4 text-left font-bold">Concepto</th>
                        <th class="px-4 py-4 text-left font-bold">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($registros as $r): ?>
                    <tr class="border-b text-white transition-colors"
                        style="border-color:var(--rojo-mid);"
                        onmouseover="this.style.backgroundColor='var(--rojo-hover)'"
                        onmouseout="this.style.backgroundColor='transparent'">
                        <td class="px-4 py-3" style="color:#9ca3af;"><?= (int) $r['id'] ?></td>
                        <td class="px-4 py-3 text-sm" style="color:#d1d5db;">
                            <?= date('d/m/Y', strtotime($r['fecha'])) ?>
                            <span style="color:#9ca3af;"> <?= date('H:i', strtotime($r['fecha'])) ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($r['tipo'] === 'ingreso'): ?>
                                <span class="font-bold text-sm px-3 py-1 rounded-full" style="background-color:#134e2a; color:#4ade80;">
                                    ▲ Ingreso
                                </span>
                            <?php else: ?>
                                <span class="font-bold text-sm px-3 py-1 rounded-full" style="background-color:#4a0e0e; color:#fca5a5;">
                                    ▼ Retiro
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 font-bold <?= $r['tipo'] === 'ingreso' ? 'text-green-400' : '' ?>"
                            <?= $r['tipo'] === 'retiro' ? 'style="color:#fca5a5;"' : '' ?>>
                            <?= $r['tipo'] === 'ingreso' ? '+' : '-' ?>$<?= number_format((float) $r['valor'], 0, ',', '.') ?>
                        </td>
                        <td class="px-4 py-3" style="color:#d1d5db;"><?= View::escape($r['concepto']) ?></td>
                        <td class="px-4 py-3 text-sm" style="color:#9ca3af;"><?= View::escape($r['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-12 text-xl" style="color:#9ca3af;">
                            Sin registros para este período
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Botón regresar -->
<a href="/caja"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg transition-all"
   style="background-color:var(--oro); color:var(--rojo-dark);"
   onmouseover="this.style.backgroundColor='var(--oro-light)'"
   onmouseout="this.style.backgroundColor='var(--oro)'">
    ← REGRESAR
</a>

</body>
</html>

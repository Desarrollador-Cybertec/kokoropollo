<?php
declare(strict_types=1);
use App\Core\{Csrf, View};

$total               = isset($total)               ? (float)  $total               : 0.0;
$ingresosHoy         = isset($ingresosHoy)         ? (float)  $ingresosHoy         : 0.0;
$retirosHoy          = isset($retirosHoy)          ? (float)  $retirosHoy          : 0.0;
$hoy                 = isset($hoy)                 ? (string) $hoy                 : date('Y-m-d');
$ayer                = isset($ayer)                ? (string) $ayer                : date('Y-m-d', strtotime('-1 day'));
$lunEs               = isset($lunEs)               ? (string) $lunEs               : date('Y-m-d', strtotime('monday this week'));
$priMes              = isset($priMes)              ? (string) $priMes              : date('Y-m-01');
$dashboardUrl        = isset($dashboardUrl)        ? (string) $dashboardUrl        : '/dashboard';
$movimientosHoy      = (isset($movimientosHoy)  && is_array($movimientosHoy))  ? $movimientosHoy  : [];
$ventasPendientesHoy = isset($ventasPendientesHoy) ? (float)  $ventasPendientesHoy : 0.0;
$esAdmin             = (bool) ($esAdmin             ?? false);
$aperturaHoy         = (isset($aperturaHoy)     && is_array($aperturaHoy))     ? $aperturaHoy     : null;
$cierreHoy           = (isset($cierreHoy)       && is_array($cierreHoy))       ? $cierreHoy       : null;
$precalc             = (isset($precalc)         && is_array($precalc))         ? $precalc         : [];
$denominaciones      = (isset($denominaciones)  && is_array($denominaciones))  ? $denominaciones  : [];

$pageTitle = 'Caja — Kokoro Pollo';
require dirname(__DIR__) . '/partials/head.php';
?>
<body class="min-h-screen py-8 pb-28" style="background:linear-gradient(135deg,#3b0a0a 0%,#4a0e0e 40%,#2b1a1a 100%);">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-3xl mx-auto px-4">

    <h1 class="text-4xl font-black text-center tracking-wide mb-6" style="color:var(--oro);">💰 CAJA</h1>

    <!-- Total -->
    <div class="bg-white text-6xl font-black text-center py-8 rounded-2xl shadow-2xl mb-4 tracking-wider"
         style="color:var(--rojo-dark);">
        $<?= number_format($total, 0, ',', '.') ?>
    </div>

    <!-- Mini-resumen del día -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="rounded-xl px-4 py-3 text-center" style="background-color:#134e2a;">
            <div class="text-xs font-bold uppercase tracking-wider text-green-300 mb-1">Ingresos hoy</div>
            <div class="text-2xl font-black text-green-300">+$<?= number_format($ingresosHoy, 0, ',', '.') ?></div>
        </div>
        <div class="rounded-xl px-4 py-3 text-center" style="background-color:#4a0e0e;">
            <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#fca5a5;">Retiros hoy</div>
            <div class="text-2xl font-black" style="color:#fca5a5;">-$<?= number_format($retirosHoy, 0, ',', '.') ?></div>
        </div>
    </div>

    <?php if ($esAdmin): ?>
    <!-- ══════════════════════════════════════════════════════════
         APERTURA — integrada en caja
    ══════════════════════════════════════════════════════════ -->

    <?php if (!$aperturaHoy): ?>
    <!-- Sin apertura: formulario inline -->
    <div class="rounded-2xl shadow-xl p-6 mb-4" style="background-color:var(--rojo-card); border:2px solid #d97706;">
        <h2 class="text-xl font-black mb-1" style="color:#fbbf24;">🔓 Registrar apertura de caja</h2>
        <p class="text-sm mb-5" style="color:#9ca3af;">Cuenta el efectivo inicial antes de comenzar el día.</p>

        <form method="POST" action="/caja/apertura">
            <?= Csrf::field() ?>

            <table class="w-full text-sm mb-4">
                <thead>
                    <tr style="color:var(--oro);">
                        <th class="text-left pb-2 font-bold">Denominación</th>
                        <th class="text-center pb-2 font-bold w-24">Cantidad</th>
                        <th class="text-right pb-2 font-bold">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($denominaciones as $den): ?>
                <tr class="border-t" style="border-color:var(--rojo-mid);">
                    <td class="py-2 font-semibold text-white">
                        $<?= number_format($den, 0, ',', '.') ?>
                    </td>
                    <td class="py-2 px-2 text-center">
                        <input type="number" name="den_<?= $den ?>" min="0" value="0"
                               data-den="<?= $den ?>"
                               oninput="apertura_calcular()"
                               class="w-20 text-center font-bold py-1 px-2 rounded-lg input-dark text-base">
                    </td>
                    <td class="py-2 text-right font-bold" id="sub_<?= $den ?>" style="color:var(--oro);">
                        $0
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="border-t-2" style="border-color:var(--oro);">
                        <td colspan="2" class="pt-3 font-black text-white text-base">TOTAL BASE</td>
                        <td class="pt-3 text-right font-black text-2xl" id="apertura_total"
                            style="color:var(--oro);">$0</td>
                    </tr>
                </tfoot>
            </table>

            <input type="text" name="observaciones" placeholder="Observaciones (opcional)" maxlength="255"
                   class="w-full px-4 py-3 rounded-xl input-dark text-base mb-4">

            <button type="submit"
                    class="w-full font-black text-xl py-4 rounded-xl btn-primary">
                🔓 Registrar Apertura
            </button>
        </form>
    </div>

    <?php else: /* aperturaHoy existe */ ?>

    <!-- Banner apertura registrada -->
    <div class="rounded-xl px-5 py-3 mb-3 flex items-center justify-between gap-4 flex-wrap"
         style="background-color:#132a1e; border:1px solid #1d6b3a;">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider" style="color:#4ade80;">🔓 Caja abierta</span>
            <span class="ml-2 text-sm font-semibold" style="color:#9ca3af;">
                Base: <strong style="color:#4ade80;">$<?= number_format((float)$aperturaHoy['base_inicial'], 0, ',', '.') ?></strong>
                · <?= View::escape($aperturaHoy['nombre_usuario']) ?>
                · <?= date('H:i', strtotime($aperturaHoy['created_at'])) ?>
            </span>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         CIERRE — integrado en caja
    ══════════════════════════════════════════════════════════ -->

    <?php if ($cierreHoy): ?>
    <!-- Cierre ya registrado: resumen completo -->
    <?php
        $sobrante = (float)($cierreHoy['sobrante'] ?? 0);
        $faltante = (float)($cierreHoy['faltante'] ?? 0);
        if ($sobrante > 0) {
            $resColor = '#4ade80'; $resBg = '#0d2b1a'; $resBorde = '#1d6b3a';
            $resTxt = '✅ Sobrante: $' . number_format($sobrante, 0, ',', '.');
        } elseif ($faltante > 0) {
            $resColor = '#fca5a5'; $resBg = '#2b0a0a'; $resBorde = '#ef4444';
            $resTxt = '⚠️ Faltante: $' . number_format($faltante, 0, ',', '.');
        } else {
            $resColor = '#4ade80'; $resBg = '#0d2b1a'; $resBorde = '#1d6b3a';
            $resTxt = '✅ Cuadre exacto';
        }
    ?>
    <div class="rounded-2xl px-5 py-5 mb-4" style="background-color:<?= $resBg ?>; border:2px solid <?= $resBorde ?>;">
        <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
            <span class="font-black text-lg" style="color:<?= $resColor ?>;">
                🔒 Jornada cerrada — <?= $resTxt ?>
            </span>
            <span class="text-xs px-3 py-1 rounded-full font-bold"
                  style="background-color:rgba(0,0,0,.3); color:#9ca3af;">
                Cierre: <?= date('H:i', strtotime($cierreHoy['created_at'] ?? 'now')) ?>
            </span>
        </div>
        <p class="text-sm" style="color:#9ca3af;">
            La caja de hoy está cerrada. Mañana aparecerá el formulario de apertura aquí mismo.
        </p>
        <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
            <div class="rounded-xl px-3 py-2" style="background-color:rgba(0,0,0,.2);">
                <span style="color:#6b7280;">Dinero esperado: </span>
                <span class="font-bold text-white">$<?= number_format((float)($cierreHoy['dinero_esperado'] ?? 0), 0, ',', '.') ?></span>
            </div>
            <div class="rounded-xl px-3 py-2" style="background-color:rgba(0,0,0,.2);">
                <span style="color:#6b7280;">Dinero contado: </span>
                <span class="font-bold text-white">$<?= number_format((float)($cierreHoy['dinero_contado'] ?? 0), 0, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <?php else: /* apertura sí, cierre no */ ?>
    <!-- Formulario de cierre siempre visible -->
    <div id="seccionCierre" class="mb-4 rounded-2xl shadow-xl p-6"
         style="background-color:var(--rojo-card); border:2px solid #7c3aed;">

        <h2 class="font-black text-xl mb-5" style="color:#a78bfa;">🔒 Cierre de Caja</h2>

        <form method="POST" action="/caja/cierre">
            <?= Csrf::field() ?>

            <!-- Sección 1: Movimientos del día (precalculados, solo lectura) -->
            <div class="mb-5">
                <h3 class="font-black text-sm uppercase tracking-wider mb-3" style="color:#9ca3af;">
                    Movimientos del día (calculados automáticamente)
                </h3>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <?php
                    $mv = [
                        ['Ventas',            'ventas',             '#4ade80'],
                        ['Otras entradas',    'otras_entradas',     '#4ade80'],
                        ['Gastos operativos', 'gastos_caja',        '#fca5a5'],
                        ['Créditos',          'creditos_empleados', '#fca5a5'],
                        ['ALSÉS',             'alses',              '#9ca3af'],
                        ['Otras salidas',     'otras_salidas',      '#9ca3af'],
                    ];
                    foreach ($mv as [$lbl, $key, $col]):
                        $val = (float)($precalc[$key] ?? 0);
                    ?>
                    <div class="rounded-xl px-3 py-2" style="background-color:var(--rojo-deep); border:1px solid var(--rojo-mid);">
                        <p class="text-xs font-bold uppercase mb-1" style="color:#6b7280;"><?= $lbl ?></p>
                        <p class="font-black text-base" style="color:<?= $col ?>;">
                            $<?= number_format($val, 0, ',', '.') ?>
                        </p>
                        <input type="hidden" name="<?= $key ?>" value="<?= $val ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="otras_salidas" value="0">

                <!-- Dinero esperado calculado -->
                <?php
                    $base      = (float)($aperturaHoy['base_inicial'] ?? 0);
                    $esperado  = $base
                        + (float)($precalc['ventas']             ?? 0)
                        + (float)($precalc['otras_entradas']     ?? 0)
                        - (float)($precalc['gastos_caja']        ?? 0)
                        - (float)($precalc['creditos_empleados'] ?? 0)
                        - (float)($precalc['alses']              ?? 0);
                ?>
                <div class="rounded-xl px-4 py-3 mt-2"
                     style="background-color:#0f2a1f; border:2px solid #4ade80;">
                    <p class="text-xs font-bold uppercase" style="color:#9ca3af;">
                        💡 Dinero esperado en caja
                    </p>
                    <p class="text-3xl font-black" style="color:#4ade80;">
                        $<?= number_format($esperado, 0, ',', '.') ?>
                    </p>
                </div>
            </div>

            <!-- Sección 2: Arqueo físico -->
            <div class="mb-5">
                <h3 class="font-black text-sm uppercase tracking-wider mb-3" style="color:#9ca3af;">
                    Cuenta el efectivo en caja ahora
                </h3>
                <table class="w-full text-sm mb-3">
                    <thead>
                        <tr style="color:var(--oro);">
                            <th class="text-left pb-2 font-bold">Denominación</th>
                            <th class="text-center pb-2 font-bold w-24">Cantidad</th>
                            <th class="text-right pb-2 font-bold">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($denominaciones as $den): ?>
                    <tr class="border-t" style="border-color:var(--rojo-mid);">
                        <td class="py-2 font-semibold text-white">$<?= number_format($den, 0, ',', '.') ?></td>
                        <td class="py-2 px-2 text-center">
                            <input type="number" name="den_<?= $den ?>" min="0" value="0"
                                   data-den="<?= $den ?>"
                                   oninput="cierre_calcular()"
                                   class="w-20 text-center font-bold py-1 px-2 rounded-lg input-dark text-base">
                        </td>
                        <td class="py-2 text-right font-bold" id="csub_<?= $den ?>" style="color:var(--oro);">$0</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2" style="border-color:var(--oro);">
                            <td colspan="2" class="pt-3 font-black text-white">TOTAL CONTADO</td>
                            <td class="pt-3 text-right font-black text-2xl" id="cierre_contado"
                                style="color:var(--oro);">$0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Sección 3: Resultado en tiempo real -->
            <div id="cierre_resultado" class="rounded-xl px-5 py-4 mb-5 text-center"
                 style="background-color:var(--rojo-deep); border:2px solid var(--rojo-mid);">
                <p class="text-xs font-bold uppercase mb-1" style="color:#9ca3af;">Diferencia</p>
                <p id="cierre_diferencia_txt" class="text-3xl font-black" style="color:#9ca3af;">—</p>
            </div>

            <input type="text" name="observaciones" placeholder="Observaciones del cierre (opcional)"
                   maxlength="255" class="w-full px-4 py-3 rounded-xl input-dark text-base mb-4">

            <button type="submit"
                    class="w-full font-black text-xl py-4 rounded-xl"
                    style="background-color:#2e1065; border:2px solid #7c3aed; color:#a78bfa;">
                🔒 Registrar Cierre de Caja
            </button>
        </form>
    </div>

    <?php endif; /* cierreHoy */ ?>
    <?php endif; /* aperturaHoy */ ?>
    <?php endif; /* esAdmin */ ?>

    <!-- Ventas pendientes de liquidar -->
    <?php if ($ventasPendientesHoy > 0): ?>
    <div class="rounded-xl px-5 py-4 mb-4 flex items-center justify-between gap-4 flex-wrap"
         style="background-color:#3a2a0f; border:2px solid var(--oro);">
        <div>
            <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:var(--oro);">🛒 Ventas sin liquidar</div>
            <div class="text-2xl font-black" style="color:var(--oro);">$<?= number_format($ventasPendientesHoy, 0, ',', '.') ?></div>
        </div>
        <a href="/ventas" class="font-bold text-sm px-5 py-2 rounded-xl btn-primary">Ir a Ventas →</a>
    </div>
    <?php endif; ?>

    <!-- ALSÉS (solo admin) -->
    <?php if ($esAdmin): ?>
    <div class="rounded-2xl shadow-xl p-5 mb-4" style="background-color:var(--rojo-card); border:1px solid #78350f;">
        <h3 class="font-black text-base uppercase tracking-wider mb-3" style="color:#fbbf24;">
            🔒 ALSÉ — Retiro de Seguridad
        </h3>
        <p class="text-xs mb-3" style="color:#9ca3af;">Traslado de efectivo por seguridad. No es un gasto operativo.</p>
        <form id="formAlse" class="flex flex-wrap gap-3">
            <input type="number" step="1" min="1" placeholder="Valor" name="valor"
                   class="input-dark px-4 py-3 rounded-xl text-lg font-bold flex-1 min-w-[120px]">
            <input type="text" placeholder="Motivo" name="motivo" maxlength="255"
                   class="input-dark px-4 py-3 rounded-xl text-lg flex-[2] min-w-[180px]">
            <button type="button" onclick="registrarAlse()"
                    class="font-black text-base px-6 py-3 rounded-xl whitespace-nowrap"
                    style="background-color:#78350f; color:#fbbf24;"
                    onmouseover="this.style.backgroundColor='#92400e'"
                    onmouseout="this.style.backgroundColor='#78350f'">
                🔒 ALSÉ
            </button>
        </form>
        <div id="alertaAlse" class="hidden mt-2 text-sm font-bold px-4 py-2 rounded-xl"></div>
    </div>
    <?php endif; ?>

    <!-- Filtros del historial -->
    <div class="rounded-2xl shadow-xl p-4 mb-6" style="background-color:var(--rojo-card);">
        <p class="text-sm font-bold uppercase tracking-wider mb-3" style="color:var(--oro);">📅 Ver historial</p>
        <div class="flex flex-wrap gap-2">
            <a href="/historial?desde=<?= $hoy ?>&hasta=<?= $hoy ?>"
               class="font-bold px-5 py-2 rounded-xl text-sm btn-primary">Hoy</a>
            <a href="/historial?desde=<?= $ayer ?>&hasta=<?= $ayer ?>"
               class="font-bold px-5 py-2 rounded-xl text-sm btn-secondary">Ayer</a>
            <a href="/historial?desde=<?= $lunEs ?>&hasta=<?= $hoy ?>"
               class="font-bold px-5 py-2 rounded-xl text-sm btn-secondary">Esta semana</a>
            <a href="/historial?desde=<?= $priMes ?>&hasta=<?= $hoy ?>"
               class="font-bold px-5 py-2 rounded-xl text-sm btn-secondary">Este mes</a>
            <a href="/historial"
               class="font-bold px-5 py-2 rounded-xl text-sm btn-secondary">📋 Todo</a>
        </div>
    </div>

    <!-- Añadir / Retirar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div class="rounded-2xl p-6 shadow-xl" style="background-color:var(--rojo-card);">
            <h2 class="text-xl font-black tracking-wide mb-5 text-center uppercase text-green-400">➕ Añadir Dinero</h2>
            <form method="POST" action="/caja" class="flex flex-col gap-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="accion" value="anadir">
                <input type="number" step="1" name="valor" placeholder="Ej: 50000" required min="1"
                       class="input-green text-xl font-bold px-4 py-4 rounded-xl w-full">
                <input type="text" name="concepto" placeholder="Ej: Venta de la tarde" required maxlength="255"
                       class="input-green text-lg px-4 py-4 rounded-xl w-full">
                <button type="submit" class="font-black text-xl py-4 rounded-xl uppercase tracking-wide btn-green">
                    ✅ AÑADIR
                </button>
            </form>
        </div>
        <div class="rounded-2xl p-6 shadow-xl" style="background-color:var(--rojo-card);">
            <h2 class="text-xl font-black tracking-wide mb-5 text-center uppercase" style="color:#fca5a5;">➖ Retirar Dinero</h2>
            <form method="POST" action="/caja" class="flex flex-col gap-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="accion" value="retirar">
                <input type="number" step="1" name="valor" placeholder="Ej: 20000" required min="1"
                       class="input-red text-xl font-bold px-4 py-4 rounded-xl w-full">
                <input type="text" name="concepto" placeholder="Ej: Compra de insumos" required maxlength="255"
                       class="input-red text-lg px-4 py-4 rounded-xl w-full">
                <button type="submit" class="font-black text-xl py-4 rounded-xl uppercase tracking-wide btn-danger">
                    💸 RETIRAR
                </button>
            </form>
        </div>
    </div>

    <!-- Actividad de hoy -->
    <!-- Actividad de hoy -->
    <?php if (!empty($movimientosHoy)): ?>
    <?php
    /* ── Agrupar ventas por pedido, preservar orden cronológico ── */
    $displayRows      = [];
    $pedidosAgrupados = [];

    foreach ($movimientosHoy as $m) {
        if ($m['origen'] === 'ventas') {
            $oid = $m['orden_id'];
            if (!isset($pedidosAgrupados[$oid])) {
                $pedidosAgrupados[$oid] = [
                    'tipo'           => 'pedido',
                    'orden_id'       => $oid,
                    'total'          => 0.0,
                    'fecha'          => $m['fecha'],
                    'liquidado'      => $m['liquidado'],
                    'tipo_pedido'    => $m['tipo_pedido'] ?? 'local',
                    'nombre_cliente' => $m['nombre_cliente'] ?? null,
                    'direccion'      => $m['direccion'] ?? null,
                    'usuario'        => $m['usuario'],
                    'items'          => [],
                ];
            }
            $pedidosAgrupados[$oid]['total'] += (float) $m['valor'];
            $pedidosAgrupados[$oid]['items'][] = $m;
        } else {
            $displayRows[] = ['tipo' => 'caja', 'data' => $m, 'fecha' => $m['fecha']];
        }
    }
    foreach ($pedidosAgrupados as $pedido) {
        $displayRows[] = ['tipo' => 'pedido', 'data' => $pedido, 'fecha' => $pedido['fecha']];
    }
    usort($displayRows, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));
    ?>
    <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color:var(--rojo-card);">
        <div class="px-5 py-3 flex items-center justify-between" style="background-color:var(--rojo-mid);">
            <h3 class="font-black text-lg uppercase tracking-wider" style="color:var(--oro);">🕐 Actividad de hoy</h3>
            <span class="text-xs font-semibold" style="color:#9ca3af;"><?= count($displayRows) ?> evento(s)</span>
        </div>
        <div style="max-height:380px; overflow-y:auto;">
        <?php foreach ($displayRows as $rowIdx => $row):
            if ($row['tipo'] === 'caja'):
                $m         = $row['data'];
                $esIngreso = $m['tipo'] === 'ingreso';
                $esRetiro  = $m['tipo'] === 'retiro';
                $color     = $esRetiro ? '#fca5a5' : '#4ade80';
                $signo     = $esRetiro ? '▼' : '▲';
        ?>
        <!-- Fila caja: ingreso / retiro / liquidación / crédito -->
        <div class="flex items-center justify-between px-4 py-3 border-b"
             style="border-color:var(--rojo-mid);">
            <div class="flex items-center gap-3 min-w-0">
                <span class="font-black text-sm" style="color:<?= $color ?>;"><?= $signo ?></span>
                <div class="min-w-0">
                    <p class="font-semibold text-sm text-white leading-tight truncate">
                        <?= View::escape($m['concepto'] ?: '—') ?>
                    </p>
                    <p class="text-xs" style="color:#6b7280;"><?= View::escape($m['usuario']) ?></p>
                </div>
            </div>
            <div class="text-right shrink-0 ml-3">
                <p class="font-black text-sm" style="color:<?= $color ?>;">
                    <?= $esRetiro ? '-' : '+' ?>$<?= number_format((float)$m['valor'], 0, ',', '.') ?>
                </p>
                <p class="text-xs" style="color:#6b7280;"><?= date('H:i', strtotime($m['fecha'])) ?></p>
            </div>
        </div>

        <?php else:
            $p        = $row['data'];
            $esLlevar = ($p['tipo_pedido'] ?? 'local') === 'llevar';
            $uid      = 'act_' . $rowIdx;
        ?>
        <!-- Bloque pedido agrupado (desplegable) -->
        <div class="border-b" style="border-color:var(--rojo-mid);">
            <button onclick="document.getElementById('<?= $uid ?>').classList.toggle('hidden')"
                    class="w-full flex items-center justify-between px-4 py-3 text-left tr-dark transition-all">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="text-base"><?= $esLlevar ? '🛵' : '🏠' ?></span>
                    <div class="min-w-0">
                        <p class="font-black text-sm leading-tight" style="color:var(--oro);">
                            Pedido #<?= View::escape($p['orden_id']) ?>
                            <?php if ($esLlevar): ?>
                                <span class="font-semibold text-xs ml-1" style="color:#34d399;">· Para llevar</span>
                            <?php endif; ?>
                            <?php if (!empty($p['liquidado'])): ?>
                                <span class="text-xs ml-1" style="color:#6b7280;">✓</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs leading-tight" style="color:#6b7280;">
                            <?= count($p['items']) ?> ítem(s) · <?= View::escape($p['usuario']) ?>
                            <?php if ($esLlevar && !empty($p['nombre_cliente'])): ?>
                                · <?= View::escape($p['nombre_cliente']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="text-right shrink-0 ml-3 flex items-center gap-2">
                    <div>
                        <p class="font-black text-sm" style="color:var(--oro);">
                            +$<?= number_format((float)$p['total'], 0, ',', '.') ?>
                        </p>
                        <p class="text-xs" style="color:#6b7280;"><?= date('H:i', strtotime($p['fecha'])) ?></p>
                    </div>
                    <span class="text-xs" style="color:#6b7280;">▾</span>
                </div>
            </button>
            <!-- Detalle desplegable -->
            <div id="<?= $uid ?>" class="hidden">
                <?php if ($esLlevar && (!empty($p['nombre_cliente']) || !empty($p['direccion']))): ?>
                <div class="px-4 py-2 text-xs flex flex-wrap gap-x-3"
                     style="background-color:rgba(52,211,153,.07); color:#34d399; border-top:1px solid var(--rojo-mid);">
                    <?php if (!empty($p['nombre_cliente'])): ?>👤 <?= View::escape($p['nombre_cliente']) ?><?php endif; ?>
                    <?php if (!empty($p['direccion'])): ?>&nbsp;· 📍 <?= View::escape($p['direccion']) ?><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php foreach ($p['items'] as $item): ?>
                <div class="flex justify-between items-center px-5 py-2 text-sm"
                     style="border-top:1px solid var(--rojo-mid); color:#d1d5db;">
                    <span><?= View::escape($item['concepto'] ?: '—') ?></span>
                    <span class="font-bold" style="color:var(--oro);">
                        +$<?= number_format((float)$item['valor'], 0, ',', '.') ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="rounded-2xl p-6 text-center" style="background-color:var(--rojo-card);">
        <p class="text-lg" style="color:#9ca3af;">Sin actividad registrada hoy</p>
    </div>
    <?php endif; ?>

</div>

<script>
const CSRF_CAJA        = document.querySelector('meta[name="csrf-token"]').content;
const ESPERADO_CIERRE  = <?= $esperado ?? 0 ?>;

/* ── Apertura: calcular base ── */
function apertura_calcular() {
    let total = 0;
    document.querySelectorAll('[name^="den_"][data-den]').forEach(inp => {
        if (inp.closest('form[action="/caja/apertura"]') === null) return;
        const den  = parseFloat(inp.dataset.den);
        const cant = parseInt(inp.value) || 0;
        const sub  = den * cant;
        const el   = document.getElementById('sub_' + den);
        if (el) el.textContent = '$' + sub.toLocaleString('es-CO');
        total += sub;
    });
    const tot = document.getElementById('apertura_total');
    if (tot) tot.textContent = '$' + total.toLocaleString('es-CO');
}

/* ── Cierre: calcular arqueo y diferencia ── */
function cierre_calcular() {
    let contado = 0;
    document.querySelectorAll('#seccionCierre [name^="den_"][data-den]').forEach(inp => {
        const den  = parseFloat(inp.dataset.den);
        const cant = parseInt(inp.value) || 0;
        const sub  = den * cant;
        const el   = document.getElementById('csub_' + den);
        if (el) el.textContent = '$' + sub.toLocaleString('es-CO');
        contado += sub;
    });
    const contadoEl = document.getElementById('cierre_contado');
    if (contadoEl) contadoEl.textContent = '$' + contado.toLocaleString('es-CO');

    const dif    = contado - ESPERADO_CIERRE;
    const difEl  = document.getElementById('cierre_diferencia_txt');
    const resEl  = document.getElementById('cierre_resultado');
    if (!difEl || !resEl) return;

    if (contado === 0) {
        difEl.textContent = '—';
        difEl.style.color = '#9ca3af';
        resEl.style.borderColor = 'var(--rojo-mid)';
    } else if (dif > 0) {
        difEl.textContent = '↑ Sobrante: $' + Math.round(dif).toLocaleString('es-CO');
        difEl.style.color = '#4ade80';
        resEl.style.borderColor = '#1d6b3a';
    } else if (dif < 0) {
        difEl.textContent = '↓ Faltante: $' + Math.round(Math.abs(dif)).toLocaleString('es-CO');
        difEl.style.color = '#fca5a5';
        resEl.style.borderColor = '#ef4444';
    } else {
        difEl.textContent = '✅ Cuadre exacto';
        difEl.style.color = '#4ade80';
        resEl.style.borderColor = '#1d6b3a';
    }
}

/* ── ALSÉ ── */
async function registrarAlse() {
    const form   = document.getElementById('formAlse');
    const alerta = document.getElementById('alertaAlse');
    const valor  = parseFloat(form.querySelector('[name="valor"]').value);
    const motivo = form.querySelector('[name="motivo"]').value.trim();

    if (!valor || valor <= 0 || !motivo) {
        mostrarAlertaCaja(alerta, '⚠️ Ingresa valor y motivo.', 'err');
        return;
    }
    try {
        const res  = await fetch('/caja/alse', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_CAJA },
            body: JSON.stringify({ valor, motivo }),
        });
        const data = await res.json();
        if (data.status === 'ok') {
            form.reset();
            mostrarAlertaCaja(alerta, '✅ ALSÉ registrado.', 'ok');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarAlertaCaja(alerta, '❌ ' + (data.mensaje ?? 'Error'), 'err');
        }
    } catch {
        mostrarAlertaCaja(alerta, '❌ Error de conexión.', 'err');
    }
}

function mostrarAlertaCaja(el, texto, tipo) {
    el.textContent = texto;
    el.style.backgroundColor = tipo === 'ok' ? '#132a1e' : '#4a0e0e';
    el.style.color           = tipo === 'ok' ? '#4ade80' : '#fca5a5';
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 4000);
}
</script>

<a href="<?= View::escape($dashboardUrl) ?>"
   class="fixed bottom-5 right-5 font-black text-lg px-6 py-4 rounded-xl shadow-lg btn-primary">
    ← REGRESAR
</a>

</body>
</html>

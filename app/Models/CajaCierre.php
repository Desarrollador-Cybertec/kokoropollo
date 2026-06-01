<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CajaCierre
{
    public function existeHoy(): bool
    {
        return $this->getHoy() !== null;
    }

    public function getHoy(): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT c.*, u.nombre AS nombre_usuario
             FROM caja_cierres c
             JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.fecha = CURDATE()
             LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['denominaciones'] = $this->getDenominaciones((int) $row['id']);
        return $row;
    }

    /**
     * Precalcula los valores del día para mostrar en el formulario de cierre.
     * @return array{ventas:float, otras_entradas:float, gastos_caja:float}
     */
    public function precalcularDia(int $aperturaId): array
    {
        $pdo = Database::getInstance();
        $hoy = date('Y-m-d');

        // Ventas liquidadas del día
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(total),0) FROM ventas
             WHERE DATE(fecha) = ? AND liquidado = 1'
        );
        $stmt->execute([$hoy]);
        $ventas = (float) $stmt->fetchColumn();

        // Ingresos manuales de caja del día (excluyendo liquidaciones de ventas)
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor),0) FROM historial_caja
             WHERE DATE(fecha) = ? AND tipo = 'ingreso'
             AND concepto NOT LIKE 'Liquidaci%'
             AND concepto NOT LIKE 'Pago cr%'"
        );
        $stmt->execute([$hoy]);
        $otrasEntradas = (float) $stmt->fetchColumn();

        // Retiros manuales de caja del día (excluyendo créditos y pagos que se cuentan por separado)
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor),0) FROM historial_caja
             WHERE DATE(fecha) = ? AND tipo = 'retiro'
             AND concepto NOT LIKE 'Crédito empleado:%'"
        );
        $stmt->execute([$hoy]);
        $gastosCaja = (float) $stmt->fetchColumn();

        // ALSÉS del día
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(valor),0) FROM retiros_seguridad WHERE DATE(fecha) = ?'
        );
        $stmt->execute([$hoy]);
        $alses = (float) $stmt->fetchColumn();

        // Créditos a empleados entregados hoy
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor),0) FROM creditos_empleados WHERE DATE(created_at) = ?"
        );
        $stmt->execute([$hoy]);
        $creditosEmpleados = (float) $stmt->fetchColumn();

        return [
            'ventas'             => $ventas,
            'otras_entradas'     => $otrasEntradas,
            'gastos_caja'        => $gastosCaja,
            'alses'              => $alses,
            'creditos_empleados' => $creditosEmpleados,
        ];
    }

    /**
     * @param array<int, int> $denominaciones  [valor_denominacion => cantidad]
     */
    public function crear(
        int    $usuarioId,
        int    $aperturaId,
        float  $ventas,
        float  $otrasEntradas,
        float  $gastosCaja,
        float  $creditosEmpleados,
        float  $alses,
        float  $otrasSalidas,
        string $observaciones,
        array  $denominaciones
    ): int {
        // Calcular dinero contado desde denominaciones
        $dineroContado = 0.0;
        foreach ($denominaciones as $valor => $cantidad) {
            $dineroContado += $valor * max(0, (int) $cantidad);
        }

        // Recuperar base inicial de la apertura
        $stmt = Database::getInstance()->prepare(
            'SELECT base_inicial FROM caja_aperturas WHERE id = ?'
        );
        $stmt->execute([$aperturaId]);
        $baseInicial = (float) $stmt->fetchColumn();

        $dineroEsperado = $baseInicial + $ventas + $otrasEntradas
                        - $gastosCaja - $creditosEmpleados - $alses - $otrasSalidas;

        $sobrante = max(0.0, $dineroContado - $dineroEsperado);
        $faltante  = max(0.0, $dineroEsperado - $dineroContado);

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO caja_cierres
                 (fecha, usuario_id, apertura_id, ventas, otras_entradas, gastos_caja,
                  creditos_empleados, alses, otras_salidas,
                  dinero_esperado, dinero_contado, sobrante, faltante, observaciones)
                 VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $usuarioId, $aperturaId, $ventas, $otrasEntradas, $gastosCaja,
                $creditosEmpleados, $alses, $otrasSalidas,
                $dineroEsperado, $dineroContado, $sobrante, $faltante,
                $observaciones ?: null,
            ]);
            $cierreId = (int) $pdo->lastInsertId();

            foreach ($denominaciones as $valor => $cantidad) {
                $cantidad = max(0, (int) $cantidad);
                if ($cantidad === 0) continue;
                $pdo->prepare(
                    'INSERT INTO caja_cierre_denominaciones
                     (cierre_id, denominacion, cantidad, subtotal)
                     VALUES (?, ?, ?, ?)'
                )->execute([$cierreId, $valor, $cantidad, $valor * $cantidad]);
            }

            $pdo->commit();
            return $cierreId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ── helpers ─────────────────────────────────────────────────

    private function getDenominaciones(int $cierreId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT denominacion, cantidad, subtotal
             FROM caja_cierre_denominaciones
             WHERE cierre_id = ?
             ORDER BY denominacion DESC'
        );
        $stmt->execute([$cierreId]);
        return $stmt->fetchAll();
    }
}

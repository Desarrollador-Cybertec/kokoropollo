<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CreditoEmpleado
{
    public function all(): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT c.*,
                    e.nombre AS nombre_empleado,
                    e.usuario AS usuario_empleado,
                    u.nombre AS nombre_creador
             FROM creditos_empleados c
             JOIN usuarios e ON e.id = c.empleado_id
             JOIN usuarios u ON u.id = c.usuario_creador_id
             ORDER BY
                 FIELD(c.estado, "pendiente", "vencido", "pagado"),
                 c.fecha_compromiso_pago ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function resumen(): array
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT
                COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) AS total_pendientes,
                COUNT(CASE WHEN estado = 'vencido'   THEN 1 END) AS total_vencidos,
                COALESCE(SUM(CASE WHEN estado IN ('pendiente','vencido') THEN valor END), 0) AS cartera_total
             FROM creditos_empleados"
        );
        $stmt->execute();
        return $stmt->fetch();
    }

    /** Suma de créditos entregados hoy (para el cierre de caja) */
    public function sumHoy(): float
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT COALESCE(SUM(valor),0) FROM creditos_empleados
             WHERE DATE(created_at) = CURDATE()"
        );
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    public function crear(
        int    $empleadoId,
        float  $valor,
        string $fechaPrestamo,
        string $fechaCompromiso,
        string $observaciones,
        int    $creadorId
    ): int {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            // C-05: FOR UPDATE para evitar race condition al verificar saldo
            $stmt = $pdo->prepare('SELECT total FROM caja WHERE id = 1 FOR UPDATE');
            $stmt->execute();
            $totalCaja = (float) $stmt->fetchColumn();
            if ($valor > $totalCaja) {
                $pdo->rollBack();
                throw new \RuntimeException('Saldo en caja insuficiente para este crédito.');
            }

            // Registrar crédito
            $pdo->prepare(
                'INSERT INTO creditos_empleados
                 (empleado_id, valor, fecha_prestamo, fecha_compromiso_pago, observaciones, usuario_creador_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$empleadoId, $valor, $fechaPrestamo, $fechaCompromiso, $observaciones ?: null, $creadorId]);

            $creditoId = (int) $pdo->lastInsertId();

            // Obtener nombre del empleado para el concepto
            $nombreEmp = $pdo->prepare('SELECT nombre FROM usuarios WHERE id = ?');
            $nombreEmp->execute([$empleadoId]);
            $nombre = (string) $nombreEmp->fetchColumn();

            // Descontar de caja
            $pdo->prepare('UPDATE caja SET total = total - ? WHERE id = 1')->execute([$valor]);

            // Registrar en historial_caja
            $creador = $pdo->prepare('SELECT nombre FROM usuarios WHERE id = ?');
            $creador->execute([$creadorId]);
            $nombreCreador = (string) $creador->fetchColumn();

            $pdo->prepare(
                "INSERT INTO historial_caja (tipo, valor, concepto, usuario, fecha)
                 VALUES ('retiro', ?, ?, ?, NOW())"
            )->execute([$valor, "Crédito empleado: {$nombre}", $nombreCreador]);

            $pdo->commit();
            return $creditoId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function pagar(int $id, int $usuarioId): void
    {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            // Bloqueo de fila para evitar doble pago
            $stmt = $pdo->prepare(
                "SELECT c.id, c.valor, c.estado, e.nombre AS nombre_empleado
                 FROM creditos_empleados c
                 JOIN usuarios e ON e.id = c.empleado_id
                 WHERE c.id = ? FOR UPDATE"
            );
            $stmt->execute([$id]);
            $credito = $stmt->fetch();

            if (!$credito || $credito['estado'] === 'pagado') {
                $pdo->rollBack();
                throw new \RuntimeException('El crédito no existe o ya fue pagado.');
            }

            $valor = (float) $credito['valor'];

            // Marcar como pagado
            $pdo->prepare(
                "UPDATE creditos_empleados
                 SET estado = 'pagado', fecha_pago = CURDATE()
                 WHERE id = ?"
            )->execute([$id]);

            // Ingresar a caja
            $pdo->prepare('UPDATE caja SET total = total + ? WHERE id = 1')->execute([$valor]);

            // Registrar en historial_caja
            $creador = $pdo->prepare('SELECT nombre FROM usuarios WHERE id = ?');
            $creador->execute([$usuarioId]);
            $nombreCreador = (string) $creador->fetchColumn();

            $pdo->prepare(
                "INSERT INTO historial_caja (tipo, valor, concepto, usuario, fecha)
                 VALUES ('ingreso', ?, ?, ?, NOW())"
            )->execute([$valor, "Pago crédito: {$credito['nombre_empleado']}", $nombreCreador]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function vencer(int $id): void
    {
        Database::getInstance()->prepare(
            "UPDATE creditos_empleados SET estado = 'vencido'
             WHERE id = ? AND estado = 'pendiente'"
        )->execute([$id]);
    }

    /** Marca automáticamente como vencidos los que pasaron la fecha de compromiso */
    public function actualizarVencidos(): void
    {
        Database::getInstance()->prepare(
            "UPDATE creditos_empleados
             SET estado = 'vencido'
             WHERE estado = 'pendiente' AND fecha_compromiso_pago < CURDATE()"
        )->execute();
    }
}

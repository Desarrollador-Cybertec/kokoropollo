-- ============================================================
-- Migración 003 — Créditos a Empleados
-- ============================================================

CREATE TABLE IF NOT EXISTS `creditos_empleados` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `empleado_id`           INT UNSIGNED    NOT NULL,
    `valor`                 DECIMAL(14,2)   NOT NULL,
    `fecha_prestamo`        DATE            NOT NULL,
    `fecha_compromiso_pago` DATE            NOT NULL,
    `fecha_pago`            DATE            DEFAULT NULL,
    `estado`                ENUM('pendiente','pagado','vencido') NOT NULL DEFAULT 'pendiente',
    `observaciones`         TEXT,
    `usuario_creador_id`    INT UNSIGNED    NOT NULL,
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_credito_empleado` (`empleado_id`),
    KEY `idx_credito_estado`   (`estado`),
    KEY `idx_credito_fecha`    (`fecha_compromiso_pago`),
    CONSTRAINT `fk_credito_empleado`
        FOREIGN KEY (`empleado_id`) REFERENCES `usuarios` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_credito_creador`
        FOREIGN KEY (`usuario_creador_id`) REFERENCES `usuarios` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

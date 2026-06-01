-- ============================================================
-- Migración 004 — ALSÉS / Retiros de Seguridad
-- ============================================================

CREATE TABLE IF NOT EXISTS `retiros_seguridad` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `valor`       DECIMAL(14,2)   NOT NULL,
    `motivo`      VARCHAR(255)    NOT NULL DEFAULT '',
    `usuario_id`  INT UNSIGNED    NOT NULL,
    `fecha`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_alse_fecha`    (`fecha`),
    KEY `idx_alse_usuario`  (`usuario_id`),
    CONSTRAINT `fk_alse_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

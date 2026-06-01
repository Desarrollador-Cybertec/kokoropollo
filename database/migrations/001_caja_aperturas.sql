-- ============================================================
-- Migración 001 — Módulo Apertura de Caja
-- ============================================================

CREATE TABLE IF NOT EXISTS `caja_aperturas` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `fecha`         DATE            NOT NULL,
    `usuario_id`    INT UNSIGNED    NOT NULL,
    `base_inicial`  DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `observaciones` TEXT,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_apertura_fecha` (`fecha`),
    KEY `idx_apertura_usuario` (`usuario_id`),
    CONSTRAINT `fk_apertura_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `caja_apertura_denominaciones` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `apertura_id`   INT UNSIGNED    NOT NULL,
    `denominacion`  INT UNSIGNED    NOT NULL COMMENT 'Valor en COP: 100000, 50000, etc.',
    `cantidad`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `subtotal`      DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`),
    KEY `idx_denom_apertura` (`apertura_id`),
    CONSTRAINT `fk_denom_apertura`
        FOREIGN KEY (`apertura_id`) REFERENCES `caja_aperturas` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

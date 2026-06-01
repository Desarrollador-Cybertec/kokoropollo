-- ============================================================
-- Migración 002 — Módulo Cierre de Caja
-- ============================================================

CREATE TABLE IF NOT EXISTS `caja_cierres` (
    `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `fecha`               DATE            NOT NULL,
    `usuario_id`          INT UNSIGNED    NOT NULL,
    `apertura_id`         INT UNSIGNED    NOT NULL,
    `ventas`              DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `otras_entradas`      DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `gastos_caja`         DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `creditos_empleados`  DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `alses`               DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `otras_salidas`       DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `dinero_esperado`     DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `dinero_contado`      DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `sobrante`            DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `faltante`            DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `observaciones`       TEXT,
    `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cierre_fecha` (`fecha`),
    KEY `idx_cierre_usuario` (`usuario_id`),
    CONSTRAINT `fk_cierre_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_cierre_apertura`
        FOREIGN KEY (`apertura_id`) REFERENCES `caja_aperturas` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `caja_cierre_denominaciones` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `cierre_id`     INT UNSIGNED    NOT NULL,
    `denominacion`  INT UNSIGNED    NOT NULL COMMENT 'Valor en COP',
    `cantidad`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `subtotal`      DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`),
    KEY `idx_denom_cierre` (`cierre_id`),
    CONSTRAINT `fk_denom_cierre`
        FOREIGN KEY (`cierre_id`) REFERENCES `caja_cierres` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

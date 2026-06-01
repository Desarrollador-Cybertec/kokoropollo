-- ============================================================
-- Migración 008: Tabla de auditoría operativa
-- ============================================================

CREATE TABLE IF NOT EXISTS `auditoria` (
    `id`          INT UNSIGNED                              NOT NULL AUTO_INCREMENT,
    `usuario`     VARCHAR(60)                               NOT NULL,
    `modulo`      VARCHAR(40)                               NOT NULL,
    `accion`      ENUM('crear','editar','eliminar','pagar') NOT NULL,
    `descripcion` VARCHAR(255)                              NOT NULL,
    `fecha`       DATETIME                                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_fecha`   (`fecha`),
    KEY `idx_modulo`  (`modulo`),
    KEY `idx_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

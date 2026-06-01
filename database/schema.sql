-- ============================================================
-- Kokoro Pollo — Schema completo de base de datos
-- Motor: InnoDB | Charset: utf8mb4 | Collation: unicode_ci
-- Seguro de ejecutar con IF NOT EXISTS sobre DB existente
-- ============================================================

CREATE DATABASE IF NOT EXISTS `kokoropollo`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `kokoropollo`;

-- ─────────────────────────────────────────────────────────────
-- TABLA: usuarios
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`      INT UNSIGNED                         NOT NULL AUTO_INCREMENT,
    `nombre`  VARCHAR(100)                         NOT NULL,
    `usuario` VARCHAR(60)                          NOT NULL,
    `clave`   VARCHAR(255)                         NOT NULL,
    `rol`     ENUM('Jefe', 'Administrador', 'Empleado') NOT NULL DEFAULT 'Empleado',
    `creado`  DATETIME                             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- TABLA: inventario
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `inventario` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `articulo`    VARCHAR(150)    NOT NULL,
    `categoria`   ENUM('Pollo Crudo','Papas','Acompañamientos','Salsas','Bebidas','Otros')
                                  NOT NULL DEFAULT 'Otros',
    `cantidad`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `valor`       DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `creado`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_articulo` (`articulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- TABLA: caja  (fila única — estado actual de la caja)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `caja` (
    `id`    TINYINT UNSIGNED    NOT NULL DEFAULT 1,
    `total` DECIMAL(14,2)       NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar la fila inicial si no existe
INSERT IGNORE INTO `caja` (`id`, `total`) VALUES (1, 0.00);

-- ─────────────────────────────────────────────────────────────
-- TABLA: historial_caja
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `historial_caja` (
    `id`       INT UNSIGNED              NOT NULL AUTO_INCREMENT,
    `tipo`     ENUM('ingreso', 'retiro') NOT NULL,
    `valor`    DECIMAL(14,2)             NOT NULL,
    `concepto` VARCHAR(255)              NOT NULL DEFAULT '',
    `usuario`  VARCHAR(60)               NOT NULL DEFAULT '',
    `fecha`    DATETIME                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- TABLA: ventas  (nueva — persistencia del módulo de ventas)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ventas` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `orden_id`        VARCHAR(12)     NOT NULL DEFAULT '',
    `inventario_id`   INT UNSIGNED    NOT NULL,
    `cantidad`        INT UNSIGNED    NOT NULL DEFAULT 1,
    `precio_unitario` DECIMAL(12,2)   NOT NULL,
    `total`           DECIMAL(14,2)   NOT NULL,
    `usuario`         VARCHAR(60)     NOT NULL DEFAULT '',
    `fecha`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `liquidado`       TINYINT(1)      NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_fecha` (`fecha`),
    KEY `idx_orden` (`orden_id`),
    KEY `idx_inventario` (`inventario_id`),
    CONSTRAINT `fk_ventas_inventario`
        FOREIGN KEY (`inventario_id`)
        REFERENCES `inventario` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- TABLA: configuracion  (clave-valor para parámetros globales)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `configuracion` (
    `clave` VARCHAR(100)  NOT NULL,
    `valor` VARCHAR(500)  NOT NULL DEFAULT '',
    PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Precios iniciales en 0 (configurar desde /config)
INSERT IGNORE INTO `configuracion` (`clave`, `valor`) VALUES
    ('precio_asado_cuarto',        '0'),
    ('precio_asado_medio',         '0'),
    ('precio_asado_entero',        '0'),
    ('empaque_activo',             '0'),
    ('empaque_inventario_id',      '0'),
    ('condimentos_cuartos_offset', '0'),
    ('condimentos_pollos_por_ciclo','1000');

-- 013: Tabla de movimientos de inventario (entradas y salidas)
CREATE TABLE IF NOT EXISTS `inventario_movimientos` (
    `id`            INT UNSIGNED                 NOT NULL AUTO_INCREMENT,
    `inventario_id` INT UNSIGNED                 NOT NULL,
    `tipo`          ENUM('entrada', 'salida')     NOT NULL,
    `cantidad`      INT UNSIGNED                 NOT NULL,
    `usuario`       VARCHAR(60)                  NOT NULL,
    `creado`        DATETIME                     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_mov_inventario` (`inventario_id`),
    CONSTRAINT `fk_mov_inventario`
        FOREIGN KEY (`inventario_id`) REFERENCES `inventario`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

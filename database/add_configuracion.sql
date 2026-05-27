-- Migración: tabla de configuración general (clave-valor)
-- Ejecutar una sola vez sobre la BD kokoropollo

CREATE TABLE IF NOT EXISTS `configuracion` (
    `clave` VARCHAR(100)  NOT NULL,
    `valor` VARCHAR(500)  NOT NULL DEFAULT '',
    PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Precios iniciales en 0 (configurar desde /config)
INSERT IGNORE INTO `configuracion` (`clave`, `valor`) VALUES
    ('precio_asado_cuarto',    '0'),
    ('precio_asado_medio',     '0'),
    ('precio_asado_entero',    '0'),
    ('precio_broaster_cuarto', '0'),
    ('precio_broaster_medio',  '0'),
    ('precio_broaster_entero', '0');

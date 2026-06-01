-- ============================================================
-- Migración 009: Bebidas, Caja para Pollo, Porciones especiales
-- ============================================================

-- 1. Bebidas y empaques
INSERT INTO `inventario` (`articulo`, `categoria`, `cantidad`, `valor`) VALUES
    ('Caja para Pollo',       'Otros',   0, 0),
    ('Pony Malta',            'Bebidas', 0, 0),
    ('Cerveza Lata',          'Bebidas', 0, 0),
    ('Malta Lata',            'Bebidas', 0, 0),
    ('Refajo Lata',           'Bebidas', 0, 0),
    ('Gaseosa 250',           'Bebidas', 0, 0),
    ('Gaseosa 350',           'Bebidas', 0, 0),
    ('Gaseosa 1 Litro',       'Bebidas', 0, 0),
    ('Gaseosa 1.65 Litros',   'Bebidas', 0, 0),
    ('Gaseosa Mega 3 Litros', 'Bebidas', 0, 0),
    ('Agua 300',              'Bebidas', 0, 0),
    ('Agua 600',              'Bebidas', 0, 0),
    ('Hit 250',               'Bebidas', 0, 0),
    ('Hit 350',               'Bebidas', 0, 0),
    ('Hit Litro',             'Bebidas', 0, 0),
    ('PET',                   'Bebidas', 0, 0),
    ('Mister Tea',            'Bebidas', 0, 0);

-- 2. Porciones especiales (stock virtual alto — no se agota)
INSERT INTO `inventario` (`articulo`, `categoria`, `cantidad`, `valor`) VALUES
    ('Porción Papa Cocida', 'Acompañamientos', 9999999, 0),
    ('Porción Francesa',    'Acompañamientos', 9999999, 0),
    ('Porción de Maduro',   'Acompañamientos', 9999999, 0);

-- 3. Guardar IDs de porciones en configuracion para control desde /config
INSERT INTO `configuracion` (`clave`, `valor`)
SELECT 'porcion_papa_id', CAST(id AS CHAR)
FROM `inventario` WHERE `articulo` = 'Porción Papa Cocida' LIMIT 1
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

INSERT INTO `configuracion` (`clave`, `valor`)
SELECT 'porcion_francesa_id', CAST(id AS CHAR)
FROM `inventario` WHERE `articulo` = 'Porción Francesa' LIMIT 1
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

INSERT INTO `configuracion` (`clave`, `valor`)
SELECT 'porcion_maduro_id', CAST(id AS CHAR)
FROM `inventario` WHERE `articulo` = 'Porción de Maduro' LIMIT 1
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

-- 4. Claves de config para porciones (desactivadas por defecto)
INSERT IGNORE INTO `configuracion` (`clave`, `valor`) VALUES
    ('porcion_papa_activa',     '0'),
    ('porcion_papa_precio',     '0'),
    ('porcion_francesa_activa', '0'),
    ('porcion_francesa_precio', '0'),
    ('porcion_maduro_activa',   '0'),
    ('porcion_maduro_precio',   '0');

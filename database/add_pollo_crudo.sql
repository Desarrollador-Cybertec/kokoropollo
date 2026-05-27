-- Migración: modelo de materia prima unificada para pollo
-- Ejecutar una sola vez sobre la BD kokoropollo

-- Cualquier categoría de pollo anterior pasa a "Pollo Crudo"
UPDATE `inventario` SET `categoria` = 'Pollo Crudo' WHERE `categoria` IN ('Pollo', 'Asado', 'Broaster');

ALTER TABLE `inventario`
    MODIFY COLUMN `categoria`
        ENUM('Pollo Crudo','Papas','Acompañamientos','Salsas','Bebidas','Otros')
        NOT NULL DEFAULT 'Otros';

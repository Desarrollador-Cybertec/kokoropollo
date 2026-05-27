-- Migración: ampliar categorías del inventario al contexto real del asadero
-- Convierte registros 'Pollo' → 'Asado' antes de cambiar el ENUM

UPDATE `inventario` SET `categoria` = 'Asado' WHERE `categoria` = 'Pollo';

ALTER TABLE `inventario`
    MODIFY COLUMN `categoria`
        ENUM('Asado','Broaster','Papas','Acompañamientos','Salsas','Bebidas','Otros')
        NOT NULL DEFAULT 'Otros';

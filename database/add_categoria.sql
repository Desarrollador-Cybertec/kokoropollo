-- Migración: agregar columna `categoria` a la tabla `inventario`
-- Ejecutar una sola vez en instalaciones existentes

ALTER TABLE `inventario`
    ADD COLUMN `categoria`
        ENUM('Pollo','Papas','Salsas','Bebidas','Otros')
        NOT NULL DEFAULT 'Otros'
        AFTER `articulo`;

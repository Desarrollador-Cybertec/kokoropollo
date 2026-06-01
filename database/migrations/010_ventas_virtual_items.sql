-- ============================================================
-- Migración 010: ventas.inventario_id nullable + item_descripcion
--   Permite registrar ventas de porciones sin inventario físico
-- ============================================================

-- 1. Hacer inventario_id nullable y agregar item_descripcion
ALTER TABLE `ventas`
    MODIFY COLUMN `inventario_id` INT UNSIGNED NULL,
    ADD COLUMN `item_descripcion` VARCHAR(150) NULL AFTER `inventario_id`;

-- 2. Eliminar las porciones que se habían creado en inventario (migración 009)
DELETE FROM `inventario`
WHERE `articulo` IN ('Porción Papa Cocida', 'Porción Francesa', 'Porción de Maduro');

-- 3. Eliminar las claves de ID de porciones de configuracion (ya no se necesitan)
DELETE FROM `configuracion`
WHERE `clave` IN ('porcion_papa_id', 'porcion_francesa_id', 'porcion_maduro_id');

-- ============================================================
-- Migración 012: Compatibilidad de columnas legacy en ventas
-- Asegura columnas requeridas por dashboard/reportes en BD antiguas.
-- ============================================================

ALTER TABLE `ventas`
    ADD COLUMN IF NOT EXISTS `liquidado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fecha`;

ALTER TABLE `ventas`
    ADD COLUMN IF NOT EXISTS `tipo_pedido` ENUM('local','llevar') NOT NULL DEFAULT 'local' AFTER `usuario`,
    ADD COLUMN IF NOT EXISTS `nombre_cliente` VARCHAR(100) NULL AFTER `tipo_pedido`,
    ADD COLUMN IF NOT EXISTS `telefono` VARCHAR(20) NULL AFTER `nombre_cliente`,
    ADD COLUMN IF NOT EXISTS `direccion` VARCHAR(255) NULL AFTER `telefono`;

ALTER TABLE `ventas`
    MODIFY COLUMN `inventario_id` INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `item_descripcion` VARCHAR(150) NULL AFTER `inventario_id`;

ALTER TABLE `ventas`
    ADD KEY IF NOT EXISTS `idx_tipo_pedido` (`tipo_pedido`),
    ADD KEY IF NOT EXISTS `idx_liquidado` (`liquidado`);

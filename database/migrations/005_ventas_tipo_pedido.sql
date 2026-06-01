-- ============================================================
-- Migración 005 — Tipo de pedido en ventas (para llevar)
-- No destructiva: columnas nullable con defaults seguros
-- ============================================================

ALTER TABLE `ventas`
    ADD COLUMN IF NOT EXISTS `tipo_pedido`    ENUM('local','llevar') NOT NULL DEFAULT 'local'  AFTER `usuario`,
    ADD COLUMN IF NOT EXISTS `nombre_cliente` VARCHAR(100) DEFAULT NULL                          AFTER `tipo_pedido`,
    ADD COLUMN IF NOT EXISTS `telefono`       VARCHAR(20)  DEFAULT NULL                          AFTER `nombre_cliente`,
    ADD COLUMN IF NOT EXISTS `direccion`      VARCHAR(255) DEFAULT NULL                          AFTER `telefono`;

ALTER TABLE `ventas`
    ADD KEY IF NOT EXISTS `idx_tipo_pedido` (`tipo_pedido`);

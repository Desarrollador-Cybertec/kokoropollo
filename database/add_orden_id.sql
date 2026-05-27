-- Migración: agregar columna `orden_id` a la tabla `ventas`
-- Ejecutar una sola vez en instalaciones existentes

ALTER TABLE `ventas`
    ADD COLUMN `orden_id` VARCHAR(12) NOT NULL DEFAULT ''
        AFTER `id`,
    ADD KEY `idx_orden` (`orden_id`);

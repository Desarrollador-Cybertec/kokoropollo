-- ============================================================
-- Migración 006: Rol Jefe + eliminación de precios Broaster
-- ============================================================

-- 1. Agregar valor 'Jefe' al ENUM de roles
ALTER TABLE `usuarios`
    MODIFY COLUMN `rol` ENUM('Jefe', 'Administrador', 'Empleado')
    NOT NULL DEFAULT 'Empleado';

-- 2. Convertir todos los Administradores existentes a Jefe
--    (El Jefe tiene acceso total. Nuevas cuentas Administrador
--     tendrán el rol reducido: sin Usuarios/Config/Reportes)
UPDATE `usuarios` SET `rol` = 'Jefe' WHERE `rol` = 'Administrador';

-- 3. Eliminar precios Broaster de configuracion (el negocio no lo vende)
DELETE FROM `configuracion`
WHERE `clave` IN ('precio_broaster_cuarto', 'precio_broaster_medio', 'precio_broaster_entero');

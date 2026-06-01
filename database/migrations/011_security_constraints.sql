-- ============================================================
-- Migración 011 — Constraints de Seguridad e Integridad
-- Corrige hallazgos de auditoría de seguridad
-- ============================================================

-- A-06: Proteger saldo de caja contra valores negativos
-- MariaDB soporta CHECK constraints desde 10.2.1
ALTER TABLE `caja`
    ADD CONSTRAINT `chk_caja_total_positivo`
        CHECK (`total` >= 0.00);

-- A-05: Prevenir doble apertura de caja por día
ALTER TABLE `caja_aperturas`
    ADD UNIQUE KEY `uq_apertura_fecha` (`fecha`);

-- A-05: Prevenir doble cierre de caja por día
ALTER TABLE `caja_cierres`
    ADD UNIQUE KEY `uq_cierre_fecha` (`fecha`);

-- A-02: Extender ENUM de auditoria.accion para cubrir eventos de auth y financieros
ALTER TABLE `auditoria`
    MODIFY COLUMN `accion`
        ENUM('crear','editar','eliminar','pagar','login','logout','liquidar','ajuste','exportar')
        NOT NULL;

-- A-02: Extender ENUM de auditoria.modulo para incluir módulo de auth
-- (no es ENUM en el schema original — es VARCHAR(40), no requiere ALTER)

-- Índice adicional para acelerar búsquedas por usuario en auditoría
-- (ya existe idx_usuario en schema, verificar que no se duplique)
-- ALTER TABLE `auditoria` ADD KEY `idx_modulo_fecha` (`modulo`, `fecha`);

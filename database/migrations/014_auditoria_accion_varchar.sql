-- 014: Ampliar auditoria.accion de ENUM a VARCHAR para soportar cualquier acción
ALTER TABLE `auditoria`
    MODIFY COLUMN `accion` VARCHAR(40) NOT NULL;

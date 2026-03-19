-- Migrazione: aggiunge campi profilo alla tabella users
-- Esegui questo manualmente se non vuoi fare down -v

ALTER TABLE users
    ADD COLUMN avatar_url       VARCHAR(255) DEFAULT NULL,
    ADD COLUMN notify_login     TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN notify_order     TINYINT(1) NOT NULL DEFAULT 1;

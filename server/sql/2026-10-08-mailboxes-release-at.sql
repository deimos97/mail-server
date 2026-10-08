-- 2026-10-08 · Fase 4 (ciclo de vida, D-009). Se aplica como root.
-- Cuándo queda libre el nombre de un buzón borrado. Lo pone la web según el motivo (borrado voluntario:
-- +90 días; impago: día 90 desde el impago). `mail-provision purge-deleted` lo respeta; si es NULL,
-- deleted_at + 90 días como hasta ahora.
ALTER TABLE mailserver.mailboxes ADD COLUMN release_at TIMESTAMP NULL DEFAULT NULL AFTER deleted_at;

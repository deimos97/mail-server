-- 2026-10-08 · Fase 3 (borrar cuenta, D-009). Se aplica como root.
-- Cuándo se borró el buzón: cuenta la cuarentena del nombre (90 días); después, `mail-provision purge-deleted`
-- quita la fila y el nombre queda libre. La web ya tiene UPDATE en mailboxes.
ALTER TABLE mailserver.mailboxes ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER can_send;

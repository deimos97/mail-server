-- 2026-10-15 · Alias desde Mi cuenta (D-029). Se aplica como root.
-- mailbox_id: el buzón dueño del alias (NULL en los del sistema, como postmaster@ y abuse@, que no salen en la web).
-- deleted_at / release_at: un alias borrado se queda inactivo en cuarentena (como los buzones, D-009) para que nadie
-- reciba correo dirigido a otra persona; `mail-provision purge-deleted` quita la fila cuando llega release_at.
ALTER TABLE mailserver.aliases
  ADD COLUMN mailbox_id INT UNSIGNED NULL DEFAULT NULL AFTER destination,
  ADD COLUMN created_at TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN release_at TIMESTAMP NULL DEFAULT NULL,
  ADD KEY aliases_mailbox_id (mailbox_id);

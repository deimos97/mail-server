-- 2026-10-07 · Fase 2, bloque A. Se aplica como root.
--
-- 1) mailboxes: dueño y plan (los gestiona la web), estado y permiso de envío.
--    Los buzones existentes quedan como estaban: status = 'active', can_send = 1.
--    - status: pending (en alta) · active · suspended (recibe, pero no entra ni envía; D-009) · deleted
--    - can_send = 0 hasta que el usuario verifica su email de recuperación (D-006); lo aplica Postfix.
ALTER TABLE mailserver.mailboxes
  ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER domain_id,
  ADD COLUMN plan_id BIGINT UNSIGNED NULL AFTER user_id,
  ADD COLUMN status ENUM('pending','active','suspended','deleted') NOT NULL DEFAULT 'active' AFTER active,
  ADD COLUMN can_send TINYINT(1) NOT NULL DEFAULT 1 AFTER status,
  ADD KEY user_id (user_id);

-- 2) Contraseñas por dispositivo (D-011). Dovecot no puede probar varias filas por usuario, así que
--    cada contraseña empieza por un prefijo público ("selector", 6 caracteres) que localiza su fila;
--    Dovecot compara luego la contraseña completa con el hash bcrypt.
CREATE TABLE mailserver.app_passwords (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mailbox_id INT UNSIGNED NOT NULL,
  name VARCHAR(64) NOT NULL,
  selector CHAR(6) NOT NULL,
  password VARCHAR(255) NOT NULL,                 -- {BLF-CRYPT}$2y$…
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_used_at TIMESTAMP NULL DEFAULT NULL,
  revoked_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY mailbox_selector (mailbox_id, selector),
  CONSTRAINT app_passwords_mailbox FOREIGN KEY (mailbox_id) REFERENCES mailserver.mailboxes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La web crea y revoca (revoked_at); no borra. Dovecot/Postfix (mailuser) ya tienen SELECT en mailserver.*
GRANT SELECT, INSERT, UPDATE ON mailserver.app_passwords TO 'portal'@'localhost';
FLUSH PRIVILEGES;

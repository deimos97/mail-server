-- 2026-10-08 · Fase 3. Se aplica como root.
--
-- Dovecot escribe aquí (servicio dict, usuario MariaDB `dovecot_dict`) y la web solo lee:
-- 1) quota_usage: copia del uso de cada buzón (plugin quota_clone). La cuota de verdad sigue siendo
--    la de maildir (maildirsize); esto es solo para enseñarla en "Mi cuenta" y en el admin.
-- 2) last_logins: último acceso por IMAP de cada buzón y dispositivo (plugin last_login).
--    device = id de app_passwords; 0 = sin contraseña de dispositivo (webmail con login único).
CREATE TABLE mailserver.quota_usage (
  username VARCHAR(255) NOT NULL,
  bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  messages INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mailserver.last_logins (
  username VARCHAR(255) NOT NULL,
  device INT UNSIGNED NOT NULL DEFAULT 0,
  last_login INT UNSIGNED NOT NULL,              -- unix timestamp (UTC)
  PRIMARY KEY (username, device)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- El usuario dovecot_dict se crea aparte, con una contraseña generada en el servidor (no va al repo):
--   CREATE USER 'dovecot_dict'@'127.0.0.1' IDENTIFIED BY '…';
GRANT SELECT, INSERT, UPDATE ON mailserver.quota_usage TO 'dovecot_dict'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON mailserver.last_logins TO 'dovecot_dict'@'127.0.0.1';
GRANT SELECT ON mailserver.quota_usage TO 'portal'@'localhost';
GRANT SELECT ON mailserver.last_logins TO 'portal'@'localhost';
FLUSH PRIVILEGES;

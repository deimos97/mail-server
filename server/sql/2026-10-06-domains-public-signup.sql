-- 2026-10-06 · Dominios que se ofrecen en el alta de la web (Fase 1).
-- Se aplica como root: la web (usuario `portal`) no puede alterar tablas de `mailserver`.
-- Postfix y Dovecot leen columnas concretas, así que añadir columnas no les afecta.

ALTER TABLE mailserver.domains
  ADD COLUMN public_signup TINYINT(1) NOT NULL DEFAULT 0 AFTER active,
  ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER public_signup;

UPDATE mailserver.domains SET public_signup = 1 WHERE name = 'unagrandeylibre.es';

-- La web solo puede cambiar estas dos columnas (desde el admin), nada más del dominio.
GRANT UPDATE (public_signup, sort_order) ON mailserver.domains TO 'portal'@'localhost';
FLUSH PRIVILEGES;

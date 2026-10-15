<?php

/**
 * Marca de unagrandeylibre.es en Roundcube: botón "Mi cuenta" en el menú, que vuelve a la web.
 * El logo y el nombre van en config.inc.php (skin_logo, product_name). Fuente: server/roundcube/ del repo.
 *
 * Además, una identidad por cada alias activo del buzón (para poder enviar desde él): se sincroniza al cargar
 * el correo, al redactar y en Ajustes → Identidades, así un alias nuevo aparece sin volver a entrar. Solo
 * borra las identidades que puso (o adoptó) este plugin, apuntadas en las preferencias del usuario: las que
 * cree el usuario a mano no se tocan (aunque Postfix no le deja enviar desde lo que no es suyo).
 * Lee mailserver.aliases y mailserver.mailboxes: el usuario `roundcube` de la BD tiene SELECT en esas columnas.
 */
class unagrandeylibre extends rcube_plugin
{
    public $task = '.*';

    private const ACCOUNT_URL = 'https://unagrandeylibre.es/cuenta';

    private const PREF = 'ugl_alias_identities';

    public function init()
    {
        $this->add_texts('localization/');
        $this->include_stylesheet('unagrandeylibre.css');

        $this->add_button([
            'type' => 'link',
            'href' => self::ACCOUNT_URL,
            'class' => 'mi-cuenta',
            'classsel' => 'mi-cuenta',
            'innerclass' => 'inner',
            'label' => 'unagrandeylibre.mi_cuenta',
            'title' => 'unagrandeylibre.mi_cuenta_titulo',
        ], 'taskbar');

        $this->add_hook('startup', [$this, 'startup']);
    }

    public function startup($args)
    {
        $pages = ['mail' => ['', 'show', 'compose'], 'settings' => ['identities', 'edit-identity']];
        if (in_array($args['action'], $pages[$args['task']] ?? [], true) && empty($_REQUEST['_remote'])) {
            try {
                $this->sync_alias_identities();
            } catch (Throwable $e) {
                rcube::raise_error($e, true, false);   // nunca rompe el webmail
            }
        }

        return $args;
    }

    private function sync_alias_identities(): void
    {
        $rcmail = rcmail::get_instance();
        $user = $rcmail->user;
        if (empty($user->ID)) {
            return;
        }
        $login = strtolower($user->get_username());

        $result = $rcmail->get_dbh()->query(
            'SELECT a.source FROM mailserver.aliases a JOIN mailserver.mailboxes m ON m.id = a.mailbox_id'
            .' WHERE m.email = ? AND m.active = 1 AND a.active = 1', $login);
        $aliases = [];
        while ($result && ($row = $rcmail->get_dbh()->fetch_assoc($result))) {
            $aliases[] = strtolower($row['source']);
        }

        $managed = (array) ($user->get_prefs()[self::PREF] ?? []);
        $identities = $user->list_identities();
        $byEmail = [];
        $name = '';
        foreach ($identities as $identity) {
            $byEmail[strtolower($identity['email'])] = $identity;
            if ($identity['standard']) {
                $name = $identity['name'];
            }
        }

        $changed = false;
        foreach ($aliases as $alias) {
            if (!isset($byEmail[$alias])) {
                $user->insert_identity(['email' => $alias, 'name' => $name, 'standard' => 0]);
            }
            if (!in_array($alias, $managed, true)) {
                $managed[] = $alias;   // también adopta la que ya creó a mano para ese alias
                $changed = true;
            }
        }

        foreach ($managed as $i => $email) {
            if (in_array($email, $aliases, true)) {
                continue;
            }
            $identity = $byEmail[$email] ?? null;
            if ($identity && !$identity['standard']) {
                $user->delete_identity($identity['identity_id']);
            }
            unset($managed[$i]);
            $changed = true;
        }

        if ($changed) {
            $user->save_prefs([self::PREF => array_values($managed)]);
        }
    }
}

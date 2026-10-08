<?php

/**
 * Marca de unagrandeylibre.es en Roundcube: botón "Mi cuenta" en el menú, que vuelve a la web.
 * El logo y el nombre van en config.inc.php (skin_logo, product_name). Fuente: server/roundcube/ del repo.
 */
class unagrandeylibre extends rcube_plugin
{
    public $task = '.*';

    private const ACCOUNT_URL = 'https://unagrandeylibre.es/cuenta';

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
    }
}

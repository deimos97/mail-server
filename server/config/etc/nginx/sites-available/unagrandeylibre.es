# Web (portal/). HTTP: solo reto ACME y redirección a HTTPS.
server {
    listen 80;
    listen [::]:80;
    server_name unagrandeylibre.es www.unagrandeylibre.es autoconfig.unagrandeylibre.es autodiscover.unagrandeylibre.es;

    location ^~ /.well-known/acme-challenge/ {
        root /var/www/mail;
    }
    location / {
        return 301 https://$host$request_uri;
    }
}

# Web (portal/). Laravel servido desde la release activa.
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name unagrandeylibre.es;

    ssl_certificate     /etc/letsencrypt/live/unagrandeylibre.es/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/unagrandeylibre.es/privkey.pem;

    root /var/www/portal/current/public;
    index index.php;
    charset utf-8;
    client_max_body_size 12M;

    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # PostHog (analítica) a través de nuestro dominio: los adblockers no lo bloquean y no hay
    # peticiones del navegador a terceros. Resolución con unbound para seguir sus cambios de IP.
    location ^~ /ingest/static/ {
        resolver 127.0.0.1 valid=300s;
        set $posthog_assets eu-assets.i.posthog.com;
        rewrite ^/ingest/(.*)$ /$1 break;
        proxy_pass https://$posthog_assets;
        proxy_set_header Host eu-assets.i.posthog.com;
        proxy_ssl_server_name on;
        proxy_set_header Cookie "";
    }
    location ^~ /ingest/ {
        resolver 127.0.0.1 valid=300s;
        set $posthog_api eu.i.posthog.com;
        rewrite ^/ingest/(.*)$ /$1 break;
        proxy_pass https://$posthog_api;
        proxy_set_header Host eu.i.posthog.com;
        proxy_ssl_server_name on;
        proxy_set_header X-Forwarded-For $remote_addr;
        proxy_set_header Cookie "";
        client_max_body_size 10M;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    # CSS/JS/fuentes con hash en el nombre: caché de un año (expires no anula los add_header del server)
    location ^~ /build/ {
        expires max;
        access_log off;
    }
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/portal.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    # Solo index.php se ejecuta; cualquier otro .php, ficheros ocultos y .env, fuera.
    location ~ \.php$ { return 404; }
    location ~ /\.(?!well-known) { deny all; }
}

# www -> apex
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.unagrandeylibre.es;
    ssl_certificate     /etc/letsencrypt/live/unagrandeylibre.es/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/unagrandeylibre.es/privkey.pem;
    return 301 https://unagrandeylibre.es$request_uri;
}

# Autoconfiguración de clientes de correo (contenido en la Fase 2 del roadmap)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name autoconfig.unagrandeylibre.es autodiscover.unagrandeylibre.es;
    ssl_certificate     /etc/letsencrypt/live/unagrandeylibre.es/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/unagrandeylibre.es/privkey.pem;
    return 404;
}

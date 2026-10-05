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

    location / {
        try_files $uri $uri/ /index.php?$query_string;
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

#!/bin/sh
set -e

DOMAIN="${DOMAIN:-fotoskazka.ru}"
CERT="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
CONF="/etc/nginx/conf.d/default.conf"

mkdir -p /etc/nginx/snippets

# Общие security-заголовки для публичных ответов (включаются в server/location).
cat > /etc/nginx/snippets/security-headers.conf <<'HEADERS'
add_header Strict-Transport-Security "max-age=31536000" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy $csp_policy always;
HEADERS

cat > "$CONF" <<EOF
map \$request_uri \$csp_policy {
    default "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: https:; media-src 'self' https:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self' https:; frame-src 'self' https://www.youtube.com https://youtube.com https://rutube.ru https://vk.com https://vkvideo.ru";
    ~^/admin "";
    ~^/livewire "";
    ~^/ar/ "";
}
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN} _;

    client_max_body_size 210M;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    root /var/www/html/public;
    index index.php index.html;
    charset utf-8;

    include /etc/nginx/snippets/security-headers.conf;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; try_files \$uri /index.php?\$query_string; }

    # Хэшированные ассеты Vite: годовой immutable-кэш.
    location ^~ /build/ {
        include /etc/nginx/snippets/security-headers.conf;
        add_header Cache-Control "public, max-age=31536000, immutable" always;
        access_log off;
        try_files \$uri =404;
    }

    # Таргеты WebAR (.mind) сжимаются gzip примерно вдвое; gzip_proxied —
    # чтобы сжимать и запросы, пришедшие через CDN.
    location ~* \.mind\$ {
        gzip on;
        gzip_comp_level 6;
        gzip_proxied any;
        gzip_vary on;
        gzip_types application/octet-stream;
        try_files \$uri =404;
    }

    # Видео отдаёт nginx по X-Accel-Redirect из VideoController@stream
    # (VIDEO_X_ACCEL=true): Range/ETag/304 без занятия php-fpm воркера.
    # internal — напрямую по этому адресу файл не открыть.
    location ^~ /protected-videos/ {
        internal;
        alias /var/www/html/storage/app/public/;
        types { }
        default_type video/mp4;
        add_header X-Content-Type-Options nosniff;
    }

    location ~ \.php\$ {
        try_files \$uri =404;
        # При каждом запросе заново резолвим docker-DNS: nginx кэширует
        # IP от fastcgi_pass app:9000 на постоянно, и после пересоздания
        # app контейнера (новый IP) сайт падает в 502.
        resolver 127.0.0.11 valid=10s ipv6=off;
        set \$fastcgi_upstream app;
        fastcgi_pass \$fastcgi_upstream:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_param HTTPS on;
        fastcgi_read_timeout 300;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

if [ -f "$CERT" ]; then
    cat >> "$CONF" <<EOF

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name ${DOMAIN} www.${DOMAIN};

    client_max_body_size 210M;

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;

    root /var/www/html/public;
    index index.php index.html;
    charset utf-8;

    include /etc/nginx/snippets/security-headers.conf;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; try_files \$uri /index.php?\$query_string; }

    # Хэшированные ассеты Vite: годовой immutable-кэш.
    location ^~ /build/ {
        include /etc/nginx/snippets/security-headers.conf;
        add_header Cache-Control "public, max-age=31536000, immutable" always;
        access_log off;
        try_files \$uri =404;
    }

    # Таргеты WebAR (.mind) сжимаются gzip примерно вдвое; gzip_proxied —
    # чтобы сжимать и запросы, пришедшие через CDN.
    location ~* \.mind\$ {
        gzip on;
        gzip_comp_level 6;
        gzip_proxied any;
        gzip_vary on;
        gzip_types application/octet-stream;
        try_files \$uri =404;
    }

    # Видео отдаёт nginx по X-Accel-Redirect из VideoController@stream
    # (VIDEO_X_ACCEL=true): Range/ETag/304 без занятия php-fpm воркера.
    # internal — напрямую по этому адресу файл не открыть.
    location ^~ /protected-videos/ {
        internal;
        alias /var/www/html/storage/app/public/;
        types { }
        default_type video/mp4;
        add_header X-Content-Type-Options nosniff;
    }

    location ~ \.php\$ {
        try_files \$uri =404;
        # При каждом запросе заново резолвим docker-DNS: nginx кэширует
        # IP от fastcgi_pass app:9000 на постоянно, и после пересоздания
        # app контейнера (новый IP) сайт падает в 502.
        resolver 127.0.0.11 valid=10s ipv6=off;
        set \$fastcgi_upstream app;
        fastcgi_pass \$fastcgi_upstream:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_param HTTPS on;
        fastcgi_read_timeout 300;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
fi

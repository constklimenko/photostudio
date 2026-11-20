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
# Камера нужна WebAR-просмотру (location /ar/), остальные API браузера не используются.
add_header Permissions-Policy "camera=(self), xr-spatial-tracking=(self), microphone=(), geolocation=()" always;
HEADERS

# Единая точка исполнения PHP: только public/index.php. Вынесено в snippet,
# потому что лимит тела (client_max_body_size) действует в том location,
# который читает тело, а не в том, откуда был внутренний редирект.
cat > /etc/nginx/snippets/php-fpm.conf <<'PHPFPM'
fastcgi_pass $fastcgi_upstream:9000;
fastcgi_index index.php;
include fastcgi_params;
fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
fastcgi_param SCRIPT_NAME /index.php;
fastcgi_param DOCUMENT_ROOT $realpath_root;
fastcgi_param HTTPS on;
fastcgi_read_timeout 300;
fastcgi_buffers 16 16k;
fastcgi_buffer_size 32k;
fastcgi_hide_header X-Powered-By;
PHPFPM

cat > "$CONF" <<EOF
# Формат access-лога с таймингами: rt — сколько занял запрос целиком,
# urt — сколько отвечал php-fpm. Без них медленные запросы (причина 504
# на CDN) в логе не видны.
log_format timed '\$remote_addr - \$remote_user [\$time_local] "\$request" \$status \$body_bytes_sent "\$http_referer" "\$http_user_agent" "\$http_x_forwarded_for" rt=\$request_time urt=\$upstream_response_time';

map \$request_uri \$csp_policy {
    default "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: https:; media-src 'self' https:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self' https:; frame-src 'self' https://www.youtube.com https://youtube.com https://rutube.ru https://vk.com https://vkvideo.ru";
    ~^/admin "";
    ~^/livewire "";
    ~^/ar/ "";
}

# Сканеры и боты: очевидные пробы (.env, .git, wp-*, бэкапы, внутренние
# каталоги Laravel) отсекаются на уровне nginx — 403 без php-fpm. Именно 403,
# а не 444: CDN превращает оборванное соединение в 502 и это пачкает
# статистику 5xx; 4xx проходит через edge как есть.
# Ключ — \$uri (нормализованный путь без query), иначе ?cb=123 обходил бы
# правила по расширению.
map \$uri \$blocked_probe {
    default 0;
    ~*^/\.(?!well-known)                                              1;
    ~*^/(wp-admin|wp-login|wp-content|wp-includes|wp-json|xmlrpc)     1;
    ~*^/(phpmyadmin|pma|adminer|myadmin|dbadmin|sqladmin)             1;
    ~*^/(telescope|horizon|_debugbar|server-status|server-info|cgi-bin) 1;
    ~*^/(composer\.(json|lock)|package(-lock)?\.json|yarn\.lock|artisan|phpunit\.xml|sftp-config\.json|\.ftpconfig) 1;
    ~*^/(config|database|tests|routes|resources|bootstrap/cache|storage/(logs|framework|app/private))/ 1;
    ~*\.(env|sql|bak|backup|old|orig|swp|swo|tar|tar\.gz|tgz|zip|log|ini|yml|yaml|sh|dist)$ 1;
}

# Версия nginx в заголовке Server и на страницах ошибок не раскрывается.
server_tokens off;

# Таймауты против медленных клиентов (slowloris): тело и заголовки запроса
# не должны удерживать воркер бесконечно.
client_header_timeout 20s;
client_body_timeout 20s;
send_timeout 30s;

server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN} _;

    # Обычные формы и Livewire-запросы небольшие; крупные тела разрешены
    # только на /livewire/ (см. location ниже), а не на всём сайте.
    client_max_body_size 8m;

    access_log /var/log/nginx/access.log timed;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    root /var/www/html/public;
    index index.php index.html;
    charset utf-8;

    include /etc/nginx/snippets/security-headers.conf;

    # Docker-DNS резолвим при каждом запросе: nginx кэширует IP от
    # fastcgi_pass app:9000 на постоянно, и после пересоздания app
    # контейнера (новый IP) сайт падает в 502.
    resolver 127.0.0.11 valid=10s ipv6=off;
    set \$fastcgi_upstream app;

    # Пробы сканеров (.env, .git, wp-*, бэкапы) — 403 без php-fpm.
    if (\$blocked_probe) {
        return 403;
    }

    location / {
        try_files \$uri \$uri/ @laravel;
    }

    # Front controller. Наследует серверный лимит тела (8m): обычные формы.
    location @laravel {
        include /etc/nginx/snippets/php-fpm.conf;
    }

    # Тот же front controller, но с лимитом под загрузки Livewire/Filament.
    location @laravel_upload {
        client_max_body_size 110m;
        include /etc/nginx/snippets/php-fpm.conf;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; try_files \$uri /index.php?\$query_string; }

    # Единственная точка, принимающая крупные тела: временные загрузки
    # Livewire/Filament (фото, видео до 100 МБ, AR-таргеты .mind).
    location ^~ /livewire/ {
        client_max_body_size 110m;
        try_files \$uri @laravel_upload;
    }

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

    # Производные изображения (display/lightbox) отдаёт nginx по
    # X-Accel-Redirect из MediaController@display/lightbox (IMAGE_X_ACCEL=true):
    # PHP проверяет доступ и наличие файла, тело (PNG до 2 МБ) отдаёт nginx,
    # воркер php-fpm освобождается сразу.
    location ^~ /protected-images/ {
        internal;
        alias /var/www/html/storage/app/image-cache/;
        types { image/webp webp; image/png png; }
        include /etc/nginx/snippets/security-headers.conf;
        # Cache-Control приходит из контроллера (immutable) — add_header здесь
        # дал бы дубль заголовка.
    }

    # WebP-превью (thumbnails) — тоже через X-Accel-Redirect.
    location ^~ /protected-thumbnails/ {
        internal;
        alias /var/www/html/storage/app/public/thumbnails/;
        types { image/webp webp; }
        default_type image/webp;
        include /etc/nginx/snippets/security-headers.conf;
    }

    location ~ \.php\$ {
        try_files \$uri =404;
        include /etc/nginx/snippets/php-fpm.conf;
    }

    # Вложенные dot-пути (/foo/.env): map ловит только начало URI.
    location ~ /\.(?!well-known).* {
        return 403;
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

    # Обычные формы и Livewire-запросы небольшие; крупные тела разрешены
    # только на /livewire/ (см. location ниже), а не на всём сайте.
    client_max_body_size 8m;

    access_log /var/log/nginx/access.log timed;

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    # Тикеты сессий не используем: при ротации ключей утечка неинициализированного
    # ключа позволяла подделать сессию (класс CVE-2026-97629), плюс тикеты
    # завязаны на один сервер, а кэш сессий работает и без них.
    ssl_session_tickets off;

    root /var/www/html/public;
    index index.php index.html;
    charset utf-8;

    include /etc/nginx/snippets/security-headers.conf;

    # Docker-DNS резолвим при каждом запросе: nginx кэширует IP от
    # fastcgi_pass app:9000 на постоянно, и после пересоздания app
    # контейнера (новый IP) сайт падает в 502.
    resolver 127.0.0.11 valid=10s ipv6=off;
    set \$fastcgi_upstream app;

    # Пробы сканеров (.env, .git, wp-*, бэкапы) — 403 без php-fpm.
    if (\$blocked_probe) {
        return 403;
    }

    location / {
        try_files \$uri \$uri/ @laravel;
    }

    # Front controller. Наследует серверный лимит тела (8m): обычные формы.
    location @laravel {
        include /etc/nginx/snippets/php-fpm.conf;
    }

    # Тот же front controller, но с лимитом под загрузки Livewire/Filament.
    location @laravel_upload {
        client_max_body_size 110m;
        include /etc/nginx/snippets/php-fpm.conf;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; try_files \$uri /index.php?\$query_string; }

    # Единственная точка, принимающая крупные тела: временные загрузки
    # Livewire/Filament (фото, видео до 100 МБ, AR-таргеты .mind).
    location ^~ /livewire/ {
        client_max_body_size 110m;
        try_files \$uri @laravel_upload;
    }

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

    # Производные изображения (display/lightbox) отдаёт nginx по
    # X-Accel-Redirect из MediaController@display/lightbox (IMAGE_X_ACCEL=true):
    # PHP проверяет доступ и наличие файла, тело (PNG до 2 МБ) отдаёт nginx,
    # воркер php-fpm освобождается сразу.
    location ^~ /protected-images/ {
        internal;
        alias /var/www/html/storage/app/image-cache/;
        types { image/webp webp; image/png png; }
        include /etc/nginx/snippets/security-headers.conf;
        # Cache-Control приходит из контроллера (immutable) — add_header здесь
        # дал бы дубль заголовка.
    }

    # WebP-превью (thumbnails) — тоже через X-Accel-Redirect.
    location ^~ /protected-thumbnails/ {
        internal;
        alias /var/www/html/storage/app/public/thumbnails/;
        types { image/webp webp; }
        default_type image/webp;
        include /etc/nginx/snippets/security-headers.conf;
    }

    location ~ \.php\$ {
        try_files \$uri =404;
        include /etc/nginx/snippets/php-fpm.conf;
    }

    # Вложенные dot-пути (/foo/.env): map ловит только начало URI.
    location ~ /\.(?!well-known).* {
        return 403;
    }
}
EOF
fi

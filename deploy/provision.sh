#!/usr/bin/env bash
# One-time setup of a fresh Ubuntu 24.04 server for Rasoi.
#
# Run as root from the cloned repository (see deploy/README.md):
#   sudo bash /var/www/rasoi/deploy/provision.sh you@example.com [rasoi.example.com]
#
# Without a domain the app is served on <ip-with-dashes>.sslip.io, which points
# at this server and can get a free certificate. Safe to run again later, for
# example with your real domain once its DNS points here.
set -euo pipefail

EMAIL="${1:-}"
DOMAIN="${2:-}"
APP_DIR="/var/www/rasoi"
APP_USER="rasoi"
PHP="8.3"

if [[ $EUID -ne 0 ]]; then echo "Run this as root (sudo)." >&2; exit 1; fi
if [[ -z "$EMAIL" ]]; then echo "Usage: sudo bash deploy/provision.sh you@example.com [your-domain]" >&2; exit 1; fi
if [[ ! -f "$APP_DIR/artisan" ]]; then echo "Clone the repository into $APP_DIR first (see deploy/README.md)." >&2; exit 1; fi

if [[ -z "$DOMAIN" ]]; then
    IP="$(ip -4 route get 1.1.1.1 | awk '{for (i = 1; i < NF; i++) if ($i == "src") print $(i + 1)}')"
    DOMAIN="${IP//./-}.sslip.io"
fi
ADMIN_DOMAIN="admin.$DOMAIN"

step() { printf '\n\033[1;32m==> %s\033[0m\n' "$1"; }

step "Installing system packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -yq ca-certificates curl git unzip nginx postgresql redis-server supervisor \
    certbot python3-certbot-nginx ufw \
    php$PHP-fpm php$PHP-cli php$PHP-pgsql php$PHP-redis php$PHP-mbstring php$PHP-xml \
    php$PHP-curl php$PHP-zip php$PHP-intl php$PHP-bcmath php$PHP-gd php$PHP-sqlite3

if ! command -v node >/dev/null || [[ "$(node -v)" != v22.* ]]; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -yq nodejs
fi

if ! command -v composer >/dev/null; then
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm /tmp/composer-setup.php
fi

step "Firewall: SSH, HTTP and HTTPS only"
ufw allow OpenSSH >/dev/null
ufw allow 'Nginx Full' >/dev/null
ufw --force enable >/dev/null

step "Database"
ENV_FILE="$APP_DIR/.env"
if [[ -f "$ENV_FILE" ]] && grep -q '^DB_PASSWORD=.\+' "$ENV_FILE"; then
    DB_PASSWORD="$(grep '^DB_PASSWORD=' "$ENV_FILE" | cut -d= -f2-)"
else
    DB_PASSWORD="$(openssl rand -hex 24)"
fi
sudo -u postgres psql -qtAc "SELECT 1 FROM pg_roles WHERE rolname = 'rasoi'" | grep -q 1 \
    || sudo -u postgres psql -qc "CREATE USER rasoi WITH PASSWORD '$DB_PASSWORD';"
sudo -u postgres psql -qtAc "SELECT 1 FROM pg_database WHERE datname = 'rasoi'" | grep -q 1 \
    || sudo -u postgres createdb -O rasoi rasoi

step "PHP runs as the $APP_USER user"
cat > /etc/php/$PHP/fpm/pool.d/rasoi.conf <<CONF
[rasoi]
user = $APP_USER
group = $APP_USER
listen = /run/php/rasoi.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 4
CONF
systemctl restart php$PHP-fpm

step "App settings (.env)"
if [[ ! -f "$ENV_FILE" ]]; then
    cp "$APP_DIR/.env.example" "$ENV_FILE"
fi
set_env() {
    if grep -q "^$1=" "$ENV_FILE"; then
        sed -i "s|^$1=.*|$1=$2|" "$ENV_FILE"
    else
        echo "$1=$2" >> "$ENV_FILE"
    fi
}
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "https://$DOMAIN"
set_env ADMIN_DOMAIN "$ADMIN_DOMAIN"
set_env DB_PASSWORD "$DB_PASSWORD"
set_env LOG_STACK daily
set_env LOG_LEVEL info
chown "$APP_USER:$APP_USER" "$ENV_FILE"
chmod 600 "$ENV_FILE"

step "Web server for $DOMAIN and $ADMIN_DOMAIN"
cat > /etc/nginx/sites-available/rasoi <<CONF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN $ADMIN_DOMAIN;
    root $APP_DIR/public;
    index index.php;
    client_max_body_size 20m;
    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/rasoi.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
CONF
ln -sf /etc/nginx/sites-available/rasoi /etc/nginx/sites-enabled/rasoi
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

step "Queue worker (Horizon) and scheduler"
cat > /etc/supervisor/conf.d/rasoi-horizon.conf <<CONF
[program:rasoi-horizon]
command=php $APP_DIR/artisan horizon
user=$APP_USER
autostart=true
autorestart=true
stopwaitsecs=3600
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/horizon.log
CONF
echo "* * * * * $APP_USER cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/rasoi

step "Installing and building the app"
chown -R "$APP_USER:$APP_USER" "$APP_DIR"
sudo -u "$APP_USER" bash "$APP_DIR/deploy/deploy.sh" --first-run

supervisorctl reread >/dev/null
supervisorctl update >/dev/null
supervisorctl restart rasoi-horizon >/dev/null || true

step "HTTPS certificate"
if certbot --nginx --non-interactive --agree-tos --redirect -m "$EMAIL" -d "$DOMAIN" -d "$ADMIN_DOMAIN"; then
    set_env SESSION_SECURE_COOKIE true
    SCHEME=https
else
    echo "Couldn’t get a certificate yet. The site works over http for now."
    echo "Check that both names point at this server, then run this script again."
    set_env APP_URL "http://$DOMAIN"
    SCHEME=http
fi
sudo -u "$APP_USER" php "$APP_DIR/artisan" config:cache >/dev/null

cat <<DONE

Rasoi is running.

  App:     $SCHEME://$DOMAIN
  Admin:   $SCHEME://$ADMIN_DOMAIN
  Horizon: $SCHEME://$ADMIN_DOMAIN/horizon

Create your admin login:
  sudo -u $APP_USER php $APP_DIR/artisan make:filament-user
  sudo -u $APP_USER php $APP_DIR/artisan tinker --execute="App\\Models\\Staff::where('email', 'YOUR-EMAIL')->update(['role' => 'owner'])"

Update to the latest code later with:
  sudo -u $APP_USER bash $APP_DIR/deploy/deploy.sh
DONE

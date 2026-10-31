#!/usr/bin/env bash
# Pull the latest code on the checked-out branch, install, build, migrate.
# Run as the rasoi user:  sudo -u rasoi bash /var/www/rasoi/deploy/deploy.sh
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

if [[ "$(id -un)" == "root" ]]; then
    echo "Run as the app user: sudo -u rasoi bash $APP_DIR/deploy/deploy.sh" >&2
    exit 1
fi

if [[ "${1:-}" != "--first-run" ]]; then
    php artisan down --retry=15 || true
    trap 'php artisan up' EXIT
    git pull --ff-only
fi

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

npm ci --no-audit --no-fund
npm run build
rm -f public/hot

php artisan migrate --force
php artisan storage:link >/dev/null 2>&1 || true
php artisan optimize
php artisan filament:optimize
php artisan horizon:terminate >/dev/null 2>&1 || true

echo "Deployed $(git rev-parse --short HEAD) on $(git rev-parse --abbrev-ref HEAD)."

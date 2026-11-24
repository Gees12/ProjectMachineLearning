#!/bin/sh
set -e
cd /var/www/html

# Render memberi port lewat $PORT
PORT="${PORT:-10000}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Nilai acak dari Render belum berawalan "base64:"; Laravel membutuhkannya
case "$APP_KEY" in
  base64:*) ;;
  "") echo "APP_KEY belum diisi" >&2; exit 1 ;;
  *) export APP_KEY="base64:${APP_KEY}" ;;
esac

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Database bisa "tidur" (Neon scale-to-zero); coba beberapa kali
n=0
until php artisan migrate --force; do
  n=$((n+1))
  if [ "$n" -ge 5 ]; then echo "Migrasi gagal" >&2; exit 1; fi
  echo "Database belum siap, mencoba lagi dalam 5 detik..."; sleep 5
done

# Aman dijalankan berulang: hanya membuat akun/survei template yang belum ada
php artisan db:seed --force
if [ "${SEED_DEMO:-false}" = "true" ]; then
  php artisan db:seed --class=DemoResponsesSeeder --force
fi

exec apache2-foreground

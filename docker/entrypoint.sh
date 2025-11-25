#!/bin/sh
set -e

# Optional wait for Postgres
if [ -n "$DB_HOST" ]; then
  echo "[entrypoint] Waiting for Postgres ($DB_HOST:$DB_PORT)..."
  for i in $(seq 1 30); do
    if php -r "@pg_connect('host=' . getenv('DB_HOST') . ' port=' . getenv('DB_PORT') . ' dbname=' . getenv('DB_DATABASE') . ' user=' . getenv('DB_USERNAME') . ' password=' . getenv('DB_PASSWORD')) ?: exit(0);"; then
      echo "[entrypoint] Postgres is available"
      break
    fi
    sleep 1
  done
fi

# Ensure storage permissions (in case of mounted volume overriding image permissions)
chmod -R ug+rw storage bootstrap/cache || true

# Run pending migrations quietly (ignore failure in case of race during parallel workers)
if [ "$APP_ENV" != "production" ]; then
  php artisan migrate --force || echo "[entrypoint] Migrations skipped or failed"
fi

# Generate APP_KEY if missing (non-production convenience)
if [ -z "$APP_KEY" ]; then
  if grep -q '^APP_KEY=' .env && grep -q '^APP_KEY=$' .env; then
    echo "[entrypoint] Generating APP_KEY (empty in .env)";
    php artisan key:generate --force || echo "[entrypoint] APP_KEY generation failed";
  elif ! grep -q '^APP_KEY=' .env; then
    echo "[entrypoint] APP_KEY not found; generating";
    php artisan key:generate --force || echo "[entrypoint] APP_KEY generation failed";
  else
    echo "[entrypoint] APP_KEY provided via .env or environment";
  fi
fi

exec "$@"

# syntax=docker/dockerfile:1.7
# ---------- Node build stage ----------
FROM node:20-alpine AS frontend
WORKDIR /app
# Copy only files needed for dependency resolution first (better layer caching)
COPY package.json package-lock.json* yarn.lock* pnpm-lock.yaml* ./
RUN if [ -f package.json ]; then npm ci; fi
COPY resources/ ./resources/
COPY vite.config.js ./
# Build assets (fallback to no-op if scripts missing)
RUN if grep -q 'build' package.json; then npm run build; else echo "No build script"; fi

# ---------- PHP base stage ----------
FROM php:8.2-fpm-alpine AS php-base
WORKDIR /var/www/html

# System dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpq \
    libpq-dev \
    unzip \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    linux-headers \
    shadow \
    supervisor

# Build deps + PHP extensions + Redis extension then cleanup
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql pgsql intl mbstring zip bcmath opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Opcache recommended settings
RUN { \
  echo 'opcache.enable=1'; \
  echo 'opcache.enable_cli=1'; \
  echo 'opcache.validate_timestamps=0'; \
  echo 'opcache.max_accelerated_files=20000'; \
  echo 'opcache.memory_consumption=256'; \
  echo 'opcache.interned_strings_buffer=16'; \
  } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files first for dependency install
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-dev --no-autoloader
COPY . .

# Ensure .env exists BEFORE running scripts
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Ensure correct permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
  && chmod -R ug+rwx storage bootstrap/cache

# Finish composer with scripts (still --no-dev)
RUN composer install --no-interaction --prefer-dist --no-dev --no-progress \
    && composer dump-autoload --optimize

# Copy built frontend assets from build stage (if present)
COPY --from=frontend /app/resources ./resources
COPY --from=frontend /app/public ./public

# Supervisor config (optional queue worker)
RUN mkdir -p /etc/supervisor.d
COPY docker/supervisor/queue-worker.ini /etc/supervisor.d/queue-worker.ini

# Entry script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENV PHP_FPM_PORT=9000 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# ---------- Development variant ----------
FROM php-base AS dev
ENV APP_ENV=local APP_DEBUG=true
RUN sed -i 's/opcache.validate_timestamps=0/opcache.validate_timestamps=1/' /usr/local/etc/php/conf.d/opcache-recommended.ini
# Install dev dependencies separately (they may need pcntl already installed)
RUN composer install --no-interaction --prefer-dist --no-progress \
    && composer dump-autoload
CMD ["php-fpm"]

# ---------- Production optimized stage ----------
FROM php-base AS prod
ENV APP_ENV=production APP_DEBUG=false
# Pre-run Laravel optimize commands (will fail gracefully if not present)
RUN php artisan config:cache || true \
 && php artisan route:cache || true \
 && php artisan view:cache || true \
 && php artisan event:cache || true \
 && php artisan optimize || true
CMD ["php-fpm"]

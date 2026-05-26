FROM php:8.3-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    mysql-client \
    ffmpeg \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        xml \
        opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# go2rtc binary
RUN curl -fsSL https://github.com/AlexxIT/go2rtc/releases/latest/download/go2rtc_linux_amd64 \
    -o /usr/local/bin/go2rtc && chmod +x /usr/local/bin/go2rtc

WORKDIR /var/www/html

# Copy application
COPY . .

# Install PHP dependencies (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install JS and build assets
RUN npm ci --ignore-scripts && npm run build && rm -rf node_modules

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Nginx config
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# go2rtc config template (will be overridden by env at startup)
COPY docker/go2rtc/go2rtc.yaml /etc/go2rtc.yaml

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]

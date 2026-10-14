# ==========================================
# Stage 1: Build Frontend Assets with Node.js
# ==========================================
FROM node:20-alpine AS node_builder

WORKDIR /app

# Install dependencies first (better layer caching)
COPY package.json package-lock.json* ./
RUN npm install

# Copy application files needed for Vite / Tailwind CSS compilation
COPY vite.config.js ./
COPY resources/ ./resources/
COPY app/ ./app/

# Build production assets (outputs to /app/public/build)
RUN npm run build


# ==========================================
# Stage 2: Production PHP Application Server
# ==========================================
FROM php:8.3-fpm-alpine

# Install system dependencies and build libraries
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    dos2unix \
    libpng \
    libpng-dev \
    libjpeg-turbo \
    libjpeg-turbo-dev \
    freetype \
    freetype-dev \
    libzip \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    icu-libs \
    sqlite-dev \
    postgresql-dev \
    postgresql-client \
    libpq

# Configure and install required PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Copy Composer binary from official composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer definition files first to leverage Docker cache
COPY composer.json composer.lock* ./

# Install production dependencies without running scripts or autoloader yet
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy full application source code
COPY . .

# Copy compiled frontend assets from Stage 1
COPY --from=node_builder /app/public/build /var/www/html/public/build

# Finish composer autoload dump now that full source code is present
RUN composer dump-autoload --optimize --no-dev

# Copy Docker environment configurations
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini $PHP_INI_DIR/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Ensure Linux line endings and executable permissions on entrypoint
RUN dos2unix /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Set directory permissions for Laravel storage and cache
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Default port (Render overrides this with $PORT at runtime, handled by entrypoint.sh)
EXPOSE 80

# Run entrypoint script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

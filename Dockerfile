# ============================================
# Stage 1: Builder - Build dependencies
# ============================================
FROM php:8.2-fpm-alpine AS builder

WORKDIR /var/www

# Install build dependencies
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    postgresql-dev \
    libpng-dev \
    libxml2-dev \
    oniguruma-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Copy composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev dependencies for production)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --optimize-autoloader

# Copy rest of application
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Remove build dependencies to reduce image size
RUN apk del .build-deps

# ============================================
# Stage 2: Frontend builder
# ============================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copy package files
COPY package*.json ./
COPY vite.config.js ./
COPY tailwind.config.js ./
COPY postcss.config.js ./

# Install dependencies - use npm install as fallback if package-lock doesn't exist
RUN npm install --production --frozen-lockfile || npm install --production

# Copy frontend source
COPY resources ./resources

# Build assets
RUN npm run build

# ============================================
# Stage 3: Production image
# ============================================
FROM php:8.2-fpm-alpine

LABEL maintainer="HostForge <info@hostforge.nl>"
LABEL version="1.0"
LABEL description="HostForge - Secure Webhosting Billing Platform"

WORKDIR /var/www

# Install runtime dependencies only
RUN apk add --no-cache \
    postgresql-libs \
    libpng \
    libxml2 \
    oniguruma \
    fcgi \
    su-exec

# Copy PHP extensions from builder
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Copy optimized PHP configuration
COPY docker/php/production.ini /usr/local/etc/php/conf.d/zzz-production.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf

# Create non-root user with specific UID/GID
RUN addgroup -g 1000 -S hostforge && \
    adduser -u 1000 -S hostforge -G hostforge -h /home/hostforge -s /sbin/nologin

# Copy application from builder
COPY --from=builder --chown=hostforge:hostforge /var/www /var/www

# Copy built frontend assets
COPY --from=frontend-builder --chown=hostforge:hostforge /app/public/build /var/www/public/build

# Set proper permissions
RUN chown -R hostforge:hostforge /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    find /var/www/storage -type f -exec chmod 664 {} \; && \
    find /var/www/bootstrap/cache -type f -exec chmod 664 {} \;

# Health check script
COPY docker/healthcheck.sh /usr/local/bin/healthcheck
RUN chmod +x /usr/local/bin/healthcheck

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD /usr/local/bin/healthcheck

# Switch to non-root user
USER hostforge

# Expose PHP-FPM port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm", "-F"]

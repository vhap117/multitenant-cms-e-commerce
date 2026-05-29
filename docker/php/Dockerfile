FROM php:8.3-fpm

ARG PUID=1001
ARG PGID=1001

# system deps and extensions
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libexif-dev \
    libicu-dev \
    && docker-php-ext-configure exif \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# install xdebug optionally (comment out if not needed)
# RUN pecl install xdebug && docker-php-ext-enable xdebug

WORKDIR /var/www

# create developer user aligned with host UID/GID for shared volume access
RUN groupadd -g ${PGID} developer \
    && useradd -G www-data,root -u ${PUID} -g developer -d /home/developer developer \
    && mkdir -p /home/developer \
    && chown -R developer:developer /home/developer

# permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 9000

CMD ["php-fpm"]
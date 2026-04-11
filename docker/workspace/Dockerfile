FROM php:8.3-cli

ARG NODE_VERSION=20
ARG PUID=1001
ARG PGID=1001

# Install PHP extensions, Node.js toolchain, and supporting utilities
RUN apt-get update && apt-get install -y \
  git \
  unzip \
  curl \
  zip \
  libzip-dev \
  libpng-dev \
  libjpeg62-turbo-dev \
  libfreetype6-dev \
  libexif-dev \
  build-essential \
  gnupg \
  && docker-php-ext-configure exif \
  && docker-php-ext-install pdo_mysql pcntl bcmath exif \
  && curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
  && apt-get install -y nodejs \
  && npm install --global yarn pnpm \
  && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/apps/api

# Create non-root developer user aligned with host UID/GID for seamless volume access
RUN groupadd -g ${PGID} developer \
  && useradd -ms /bin/bash -u ${PUID} -g developer developer \
  && mkdir -p /home/developer/.composer \
  && chown -R developer:developer /home/developer \
  && chown -R developer:developer /var/www/apps

USER developer

CMD ["bash"]
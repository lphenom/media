FROM php:8.1-alpine3.17

# Install system dependencies: GD + FFmpeg + ImageMagick
RUN apk add --no-cache \
    git \
    unzip \
    ffmpeg \
    imagemagick \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    && docker-php-ext-configure gd \
        --with-jpeg \
        --with-webp \
        --with-freetype \
    && docker-php-ext-install gd

# Install Composer (pinned)
COPY --from=composer:2.9.5 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first for layer caching
COPY composer.json ./

# Install dependencies
RUN composer install --no-scripts --no-progress --prefer-dist

# Copy the rest of the project
COPY . .

CMD ["php", "-v"]

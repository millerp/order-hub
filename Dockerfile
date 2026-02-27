FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    pkg-config \
    build-essential \
    passwd \
    && which git && which zip && which unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql exif pcntl bcmath gd sockets zip && \
    docker-php-ext-install mbstring || (apt-get install -y libonig-dev && docker-php-ext-install mbstring)

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Kafka extension
RUN apt-get update && apt-get install -y librdkafka-dev && \
    pecl install rdkafka-6.0.5 && docker-php-ext-enable rdkafka

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Add a non-root user
ARG USER_ID=1000
ARG GROUP_ID=1000
RUN groupadd -g ${GROUP_ID} www-data-group || true && \
    useradd -u ${USER_ID} -m -g www-data-group www-user || \
    useradd -u ${USER_ID} -m www-user || true

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install RoadRunner binary for Laravel Octane
ARG RR_VERSION=2025.1.8
RUN curl -sSfL -o rr.tar.gz "https://github.com/roadrunner-server/roadrunner/releases/download/v${RR_VERSION}/roadrunner-${RR_VERSION}-linux-amd64.tar.gz" \
    && tar -xzf rr.tar.gz \
    && mv roadrunner-${RR_VERSION}-linux-amd64/rr /usr/local/bin/rr \
    && chmod +x /usr/local/bin/rr \
    && rm -rf rr.tar.gz roadrunner-${RR_VERSION}-linux-amd64

# Install FrankenPHP binary for Laravel Octane
ARG FRANKENPHP_VERSION=1.4.3
RUN curl -sSfL -o frankenphp "https://github.com/dunglas/frankenphp/releases/download/v${FRANKENPHP_VERSION}/frankenphp-linux-x86_64" \
    && chmod +x frankenphp \
    && mv frankenphp /usr/local/bin/frankenphp

# Copy Laravel entrypoint to install dependencies at container start and then run Octane
COPY docker/laravel-entrypoint.sh /usr/local/bin/laravel-entrypoint.sh
RUN chmod +x /usr/local/bin/laravel-entrypoint.sh

WORKDIR /app

# Ensure correct permissions for /app
RUN chown -R www-user:$(id -gn www-user) /app || true

# Use entrypoint to install Composer deps on startup, then start Octane
ENTRYPOINT ["/usr/local/bin/laravel-entrypoint.sh"]
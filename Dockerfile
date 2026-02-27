FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    librdkafka-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Kafka extension
RUN pecl install rdkafka && docker-php-ext-enable rdkafka

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install RoadRunner binary for Laravel Octane
ARG RR_VERSION=2025.1.8
RUN curl -sSfL -o rr.tar.gz "https://github.com/roadrunner-server/roadrunner/releases/download/v${RR_VERSION}/roadrunner-${RR_VERSION}-linux-amd64.tar.gz" \
    && tar -xzf rr.tar.gz \
    && ls -lah \
    && mv roadrunner-${RR_VERSION}-linux-amd64/rr /usr/local/bin/rr \
    && chmod +x /usr/local/bin/rr \
    && rm rr.tar.gz

WORKDIR /app

# Run Laravel Octane with RoadRunner (app code mounted per service)
CMD ["php", "artisan", "octane:start", "--server=roadrunner", "--host=0.0.0.0", "--port=8000"]
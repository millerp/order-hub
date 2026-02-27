FROM dunglas/frankenphp:1-php8.4

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
    librdkafka-dev \
    && which git && which zip && which unzip

# Install PHP extensions using the script provided in frankenphp image
RUN install-php-extensions pdo_mysql mbstring exif pcntl bcmath gd sockets zip redis rdkafka-6.0.5

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Add a non-root user
ARG USER_ID=1000
ARG GROUP_ID=1000
RUN groupadd -g ${GROUP_ID} www-data-group || true && \
    useradd -u ${USER_ID} -m -g www-data-group www-user || \
    useradd -u ${USER_ID} -m www-user || true

# FrankenPHP/Caddy needs home and config directories for the user
RUN mkdir -p /home/www-user/.config/caddy /home/www-user/.local/share/caddy \
    && chown -R www-user:www-data-group /home/www-user

# Set Caddy environment variables to use the created directories
ENV XDG_CONFIG_HOME=/home/www-user/.config
ENV XDG_DATA_HOME=/home/www-user/.local/share

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install RoadRunner binary for Laravel Octane (fallback)
ARG RR_VERSION=2025.1.8
RUN curl -sSfL -o rr.tar.gz "https://github.com/roadrunner-server/roadrunner/releases/download/v${RR_VERSION}/roadrunner-${RR_VERSION}-linux-amd64.tar.gz" \
    && tar -xzf rr.tar.gz \
    && mv roadrunner-${RR_VERSION}-linux-amd64/rr /usr/local/bin/rr \
    && chmod +x /usr/local/bin/rr \
    && rm -rf rr.tar.gz roadrunner-${RR_VERSION}-linux-amd64

# Copy Laravel entrypoint to install dependencies at container start and then run Octane
COPY docker/laravel-entrypoint.sh /usr/local/bin/laravel-entrypoint.sh
RUN chmod +x /usr/local/bin/laravel-entrypoint.sh

WORKDIR /app

# Ensure correct permissions for /app
RUN chown -R www-user:$(id -gn www-user) /app || true

# Use entrypoint to install Composer deps on startup, then start Octane
ENTRYPOINT ["/usr/local/bin/laravel-entrypoint.sh"]
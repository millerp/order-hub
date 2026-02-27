#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}Starting Order-Hub installation...${NC}"

# 1. Copy .env files for all Laravel services
services=("auth-service" "user-service" "product-service" "order-service" "payment-service" "notification-service")

echo -e "${BLUE}Configuring .env files...${NC}"
for service in "${services[@]}"; do
    if [ ! -f "$service/.env" ]; then
        cp "$service/.env.example" "$service/.env"
        echo -e "${GREEN}Created .env for $service${NC}"
        
        # Adjust database configurations in .env according to docker-compose.yml
        # Database name varies by service
        db_name=$(echo $service | sed 's/-/_/')
        sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/g" "$service/.env"
        sed -i "s/# DB_HOST=127.0.0.1/DB_HOST=orderhub-$(echo $service | sed 's/-service/-db/')/g" "$service/.env"
        sed -i "s/# DB_PORT=3306/DB_PORT=3306/g" "$service/.env"
        sed -i "s/# DB_DATABASE=laravel/DB_DATABASE=$db_name/g" "$service/.env"
        sed -i "s/# DB_USERNAME=root/DB_USERNAME=root/g" "$service/.env"
        sed -i "s/# DB_PASSWORD=/DB_PASSWORD=secret/g" "$service/.env"
        
        # Adjust Redis
        sed -i "s/REDIS_HOST=127.0.0.1/REDIS_HOST=orderhub-redis/g" "$service/.env"
        sed -i "s/SESSION_DRIVER=database/SESSION_DRIVER=redis/g" "$service/.env"

        # Set FrankenPHP as default Octane server
        sed -i "s/OCTANE_SERVER=roadrunner/OCTANE_SERVER=frankenphp/g" "$service/.env"

        # Enable Telescope
        if ! grep -q "TELESCOPE_ENABLED" "$service/.env"; then
            echo -e "\nTELESCOPE_ENABLED=true" >> "$service/.env"
        fi
    else
        echo -e ".env file already exists in $service. Skipping."
    fi
done

# 2. Spin up basic infrastructure (DBs, Redis, Kafka)
echo -e "${BLUE}Spinning up infrastructure (Databases, Redis, Kafka)...${NC}"

# Export CURRENT_UID and CURRENT_GID for docker-compose
export CURRENT_UID=$(id -u)
export CURRENT_GID=$(id -g)

# Pre-create RSA key directory to prevent Docker from creating it as a directory with wrong permissions
mkdir -p auth-service/storage/keys
# Ensure the placeholder exists and belongs to current user
touch auth-service/storage/keys/oauth-public.key
chmod 644 auth-service/storage/keys/oauth-public.key

# Clean up any legacy file-level mounts that might still be in the host's storage
for service in "${services[@]}"; do
    if [ -f "$service/storage/oauth-public.key" ]; then
        rm -f "$service/storage/oauth-public.key"
    fi
    mkdir -p "$service/storage/keys"
done

docker compose up -d auth-db user-db product-db order-db payment-db notification-db redis kafka

echo -e "${BLUE}Waiting for databases to start (15s)...${NC}"
sleep 15

# 3. Composer dependencies will be installed at container startup by the Laravel entrypoint
# Skipping pre-install here to keep flow similar to the gateway (runtime install)
echo -e "${BLUE}Skipping local Composer install: dependencies will be installed when containers start.${NC}"

# Function to wait for a container to be running
wait_for_container() {
    local container_name=$1
    local retries=30
    local wait_time=2
    echo -e "${BLUE}Waiting for $container_name to be running...${NC}"
    while [ $retries -gt 0 ]; do
        status=$(docker inspect "$container_name" --format '{{.State.Status}}' 2>/dev/null)
        if [ "$status" == "running" ]; then
            return 0
        fi
        sleep $wait_time
        ((retries--))
    done
    echo -e "${RED}Error: Container $container_name failed to start in time.${NC}"
    return 1
}

# 4. Start Auth Service first to generate keys
echo -e "${BLUE}Starting Auth service to generate keys...${NC}"
export CURRENT_UID=$(id -u)
export CURRENT_GID=$(id -g)
docker compose up -d auth-service --build

# 5. Build and initialize other Laravel services
echo -e "${BLUE}Building and starting remaining Laravel services...${NC}"
docker compose up -d user-service product-service order-service payment-service notification-service --build

# 6. Execute internal commands in Laravel containers
for service in "${services[@]}"; do
    container_name="orderhub-$service"
    
    # Wait for container to be ready before running artisan commands
    if wait_for_container "$container_name"; then
        echo -e "${BLUE}Configuring $service ($container_name)...${NC}"
        
        echo "Generating application key..."
        docker exec "$container_name" php artisan key:generate --no-interaction
        
        # 7. Generate RSA keys specifically for Auth Service and restart services that depend on it
        if [ "$service" == "auth-service" ]; then
            echo -e "${BLUE}Generating RSA keys for JWT in auth-service...${NC}"
            # Ensure the directory exists inside the container
            docker exec orderhub-auth-service mkdir -p storage/keys
            # Generate keys properly inside the new directory
            docker exec orderhub-auth-service bash -c "rm -f storage/keys/oauth-private.key storage/keys/oauth-public.key && openssl genrsa -out storage/keys/oauth-private.key 2048 && openssl rsa -in storage/keys/oauth-private.key -pubout -out storage/keys/oauth-public.key"
            # Ensure permissions on keys
            docker exec orderhub-auth-service chmod 644 storage/keys/oauth-public.key storage/keys/oauth-private.key
            
            # Since we generated new keys, and containers might have been started with a placeholder
            # and Octane might have cached it, we MUST restart the other services
            echo -e "${BLUE}Restarting services to pick up the new keys...${NC}"
            docker compose restart user-service product-service order-service payment-service notification-service
        fi
    fi
done

# 8. Initialize Kafka Topics
echo -e "${BLUE}Initializing Kafka topics...${NC}"
topics=("order.created" "payment.approved" "payment.failed" "payment.failed.dlq")
for topic in "${topics[@]}"; do
    docker exec orderhub-kafka /opt/kafka/bin/kafka-topics.sh --create --topic "$topic" --bootstrap-server localhost:9092 --if-not-exists
done

# 9. Start Gateway and Frontend
echo -e "${BLUE}Starting Gateway and Frontend...${NC}"
export CURRENT_UID=$(id -u)
export CURRENT_GID=$(id -g)
docker compose up -d gateway

echo -e "${GREEN}Installation completed successfully!${NC}"
echo -e "Access the project at http://localhost"

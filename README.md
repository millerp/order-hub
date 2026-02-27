# OrderHub - Microservices Architecture

OrderHub is a robust, event-driven microservices architecture built for a distributed marketplace. It leverages the latest stable versions of PHP 8.4, Laravel 12, MySQL 8.4 LTS, Redis, and Apache Kafka. 

## Features
- **Independent Microservices**: Services are completely decoupled with no cross-database access. 
- **Event-Driven**: State changes flow asynchronously via Kafka (`order.created`, `payment.approved`, `payment.failed`).
- **Synchronous Edges**: HTTP protocol is used strictly for synchronously required validations (e.g., stock availability checks before finalization).
- **Stateless Authorization**: JWT (RS256) is minted by the Auth Service, and its public key is distributed to all other microservices for local token validation via custom middleware.

## Event Flow Diagram
```mermaid
sequenceDiagram
    participant Client
    participant Auth
    participant Product
    participant Order
    participant Payment
    participant Notification
    
    Client->>Auth: POST /register
    Auth-->>Client: Returns JWT Token (RS256)
    
    Client->>Order: POST /orders (Requires Token)
    Order->>Product: GET /products/{id} (HTTP Sync)
    Order->>Product: POST /reserve (HTTP Sync)
    Product-->>Order: Stock Reserved successfully
    Order->>Order DB: Creates Order (status: pending)
    Order->>Payment: Publishes `order.created` (Kafka)
    Order-->>Client: Returns Order details
    
    Payment->>Payment: Consumes `order.created`
    Payment->>Payment: Processes simulation (80% success rate)
    alt Payment Succeeded
        Payment->>Order: Publishes `payment.approved`
        Payment->>Notification: Publishes `payment.approved`
        Order->>Order: Consumes `payment.approved` -> Status 'paid'
        Notification->>Notification: Sends confirmation email
    else Payment Failed
        Payment->>Order: Publishes `payment.failed`
        Order->>Order: Consumes `payment.failed` -> Status 'cancelled'
        Payment->>Payment: Send to dead-letter queue (DLQ) if technical errors persist
    end
```

## Laravel Octane (RoadRunner)
All Laravel API services run with **Laravel Octane** and **RoadRunner** for high performance: the application stays in memory between requests. Each service has:
- `config/octane.php` – Octane listeners and options
- `.rr.yaml` – RoadRunner worker/config (HTTP on `0.0.0.0:8000`, 4 workers)

The Docker image includes the RoadRunner binary; the container runs:
`php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000`.

## Setup Instructions

To simplify project startup, use the automated setup script:

```bash
chmod +x init-project.sh
./init-project.sh
```

The script will:
1. Configure `.env` files for all services.
2. Spin up the infrastructure (Databases, Redis, Kafka).
3. Install Composer dependencies inside the containers.
4. Generate application keys and RSA keys for JWT.
5. Execute database migrations.
6. Initialize Kafka topics.
7. Start the Gateway and Frontend.

---

### Manual Setup (Step-by-Step)
1. Ensure Docker Desktop is running.
2. Install PHP dependencies (including `laravel/octane`) in each service. From the repo root:
   ```bash
   cd auth-service && composer install --no-interaction && cd ..
   cd user-service && composer install --no-interaction && cd ..
   cd product-service && composer install --no-interaction && cd ..
   cd order-service && composer install --no-interaction && cd ..
   cd payment-service && composer install --no-interaction && cd ..
   cd notification-service && composer install --no-interaction && cd ..
   ```
   Or run inside containers after the first `docker compose up`:  
   `docker exec orderhub-auth-service composer install --no-interaction` (and similarly for the other services).
3. In the repository root, build and deploy the infrastructure:
   ```bash
   docker compose up -d --build
   ```
4. Initialize the Databases by running migrations for each service:
   ```bash
   docker exec orderhub-auth-service php artisan migrate:fresh
   docker exec orderhub-user-service php artisan migrate:fresh
   docker exec orderhub-product-service php artisan migrate:fresh
   docker exec orderhub-order-service php artisan migrate:fresh
   docker exec orderhub-payment-service php artisan migrate:fresh
   docker exec orderhub-notification-service php artisan migrate:fresh
   ```
5. Generate RSA keys for JWT tokens:
   ```bash
   docker exec orderhub-auth-service bash -c "openssl genrsa -out storage/oauth-private.key 2048 && openssl rsa -in storage/oauth-private.key -pubout -out storage/oauth-public.key"
   ```
   *Note: Due to Docker Volume bindings, the public key is automatically mounted into all other containers!*
6. Initialize Kafka Topics:
   ```bash
   docker exec orderhub-kafka /opt/kafka/bin/kafka-topics.sh --create --topic order.created --bootstrap-server localhost:9092
   docker exec orderhub-kafka /opt/kafka/bin/kafka-topics.sh --create --topic payment.approved --bootstrap-server localhost:9092
   docker exec orderhub-kafka /opt/kafka/bin/kafka-topics.sh --create --topic payment.failed --bootstrap-server localhost:9092
   docker exec orderhub-kafka /opt/kafka/bin/kafka-topics.sh --create --topic payment.failed.dlq --bootstrap-server localhost:9092
   ```
7. Done! Endpoints are mapped to ports `8001-8006`.
   - Start the consumers by accessing their respective containers, or adding worker entries to the compose file. e.g., `docker exec -d orderhub-payment-service php artisan kafka:consume-order`

## Kafka Topic Design
To support loose coupling, Kafka topics act as the primary communication contract:
- `order.created`: Contains `order_id`, `user_id`, `amount`, and `status`. Consumed by Payment.
- `payment.approved`: Contains `order_id` and `payment_id`. Consumed by Order and Notification.
- `payment.failed`: Contains `order_id` and `payment_id`. Consumed by Order Service.
- `payment.failed.dlq`: Dead-letter queue for persisting events failing multiple technical retries.

## Design Decisions
1. **JWT RS256 Validation**: Standard UUID/id validation is usually slow across networks. A central IDP (Auth Service) emits RS256 signed tokens using its private key. The public key is physically loaded into surrounding microservices, allowing them to decode and validate JWT offline. 
2. **Synchronous Stock Reservation**: The Product service operates as the absolute source of truth for inventory. Instead of a complex Saga pattern for overbooking avoidance, the Order service hits HTTP `/reserve` inside a transaction block to ensure atomic subtraction dynamically before finalizing the Order.

## Failure Handling Strategy & Idempotency
- **Idempotency**: All consumers (e.g., `KafkaConsumeOrderCommand` in Payment Service) verify if a `payment` record for the given `order_id` already exists (checking local DB) before attempting logic processing.
- **Failures & DLQ**: Simulated technical errors trigger the catch block. If exceptions are thrown in message parsing or processing, the event is redirected to a `.dlq` (Dead-letter queue) topic ensuring partition pointers can continue without choking.

## Trade-offs Made
- Due to strict independent microservice standards, shared configurations and code blocks (like `JwtMiddleware`) were duplicated. In larger environments, this could be extracted into an interior composer package.
- Default Laravel `web` and `api` configs were adjusted manually because standard boilerplate is heavy.
- Docker containers run via **Laravel Octane (RoadRunner)** instead of Nginx/FPM for high throughput and in-memory request handling per service.

## Future Improvements
- Implement a true API Gateway (like Kong, or KrakenD) running at port `80` acting as a proxy layer with rate limiters.
- Use OpenTelemetry for distributed tracing to correlate `Request-ID` across HTTP and Kafka hops.
- Build Circuit Breakers using Laravel HTTP facades in `OrderController`.

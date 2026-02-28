# Laravel Roadmap (3 Phases)

## Phase 1 (Implemented)
- API protection and abuse control:
  - Rate limiting on auth endpoints (`/login`, `/register`, `/refresh`).
- API observability baseline:
  - `X-Request-Id` middleware in Auth Service.
  - JSON error payload now includes `request_id`.
- Operations and reliability:
  - Scheduler in Order Service for outbox publish (`kafka:publish-outbox --once`) and outbox pruning (`outbox:prune`).
  - Scheduler in Notification Service for failed job pruning (`queue:prune-failed`).
  - Dedicated scheduler containers in Docker Compose.

## Phase 2 (Implemented)
- Domain Events + Listeners:
  - Replace direct side effects in services with first-class events/listeners.
- Contract tests between services:
  - Validate event payload contracts and API compatibility.
- API response standardization:
  - Shared response envelope (`data`, `meta`, `errors`) across services.
- Policy/RBAC consistency:
  - Remove duplicated auth checks from FormRequests and centralize in policies.

### Phase 2 - Completed Scope
- Notification Service:
  - `payment.approved` Kafka payload is now validated by a dedicated contract object (`PaymentApprovedPayload`).
  - Domain event (`PaymentApprovedReceived`) and listener (`QueuePaymentApprovedNotification`) were introduced.
  - Kafka consumer now dispatches domain events instead of directly dispatching queue jobs.
- Product Service:
  - Authorization logic duplication removed from product FormRequests; authorization remains centralized in Gate/Policy.
- API responses:
  - Standard response envelope (`data`, `meta.request_id`, `errors`) applied across Auth, Order, Product, and User service endpoints.
  - JWT middleware error responses in Product, Order, and User services now follow the same envelope and include `request_id`.
  - Exception handlers in Auth, Order, Product, and User services now include `errors` and `meta.request_id`.
  - Transitional compatibility fields were preserved where needed (for example, legacy auth/product fields consumed by current clients).

## Phase 3 (Advanced)
- Full observability:
  - OpenTelemetry + distributed trace correlation across HTTP and Kafka.
- Queue orchestration:
  - `Bus::batch`, `Bus::chain`, compensations, and richer failure workflows.
- Real-time UX:
  - Broadcast order/payment status updates to frontend.
- Internal shared package:
  - Extract JWT middleware, event DTOs, and shared conventions into a private Composer package.

## Extendable Order and Payment Management API

Laravel API for managing orders and payments with JWT authentication. The payment layer uses a strategy pattern so new gateways can be added with minimal changes.

### Requirements
- Docker + Docker Compose

### Docker Setup (PHP 8.5 Alpine + SQLite)
1. Build and start the API:
   - `docker compose up --build`
2. The API will be available at:
   - `http://localhost:8000`

The container will:
- Install PHP dependencies
- Generate `APP_KEY` and `JWT_SECRET` if missing
- Create SQLite DB at `database/database.sqlite`
- Run migrations

### Run Tests (Docker)
- `docker compose exec -T api composer test`

### Authentication (JWT)
All protected endpoints require:
```
Authorization: Bearer <token>
```

### Core Endpoints (prefix: `/api/v1`)
Auth:
- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/refresh`
- `POST /auth/logout`
- `GET /auth/me`

Orders (protected):
- `POST /orders`
- `GET /orders?status=pending|confirmed|cancelled&per_page=15`
- `GET /orders/{id}`
- `PUT/PATCH /orders/{id}`
- `DELETE /orders/{id}` (blocked if payments exist)

Payments (protected):
- `POST /orders/{id}/payments` (only when order is confirmed)
- `GET /orders/{id}/payments`
- `GET /payments?status=successful|failed|pending&method=credit_card|paypal`

### Business Rules
- Payments can only be processed for orders in the `confirmed` status.
- Orders cannot be deleted if any payments exist.
- Totals are always calculated server-side from the line items.

### Payment Gateway Extensibility
Gateways implement `App\Contracts\Payments\PaymentGatewayContract`. To add a new gateway:
1. Create a class in `app/Payments/Gateways`, e.g. `NewGateway`.
2. Implement `key()` and `charge()`, returning a `PaymentGatewayResult`.
3. Register the gateway in `config/payments.php` under `methods`.
4. Add required secrets to `.env` (e.g. `NEWGATEWAY_API_KEY`).
5. Add tests for resolver selection and payment processing.

### Postman Collection
Import `postman/order-payment-api.postman_collection.json` into Postman.
The collection includes:
- Auth flows
- Order CRUD
- Payment processing
- Example error cases

### Notes / Assumptions
- Payments are simulated and always use the order total (no partial payments).
- Allowed payment methods are configured in `config/payments.php`.

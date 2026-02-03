# Extendable Order and Payment Management API

A robust Laravel REST API for managing orders and payments with JWT authentication. The payment layer uses the **Strategy Pattern** for extensibility, allowing new payment gateways to be added with minimal code changes.

---

## 📋 Table of Contents
1. [Requirements](#requirements)
2. [Quick Start](#quick-start)
3. [API Endpoints](#api-endpoints)
4. [Authentication](#authentication)
5. [Business Rules](#business-rules)
6. [Payment Gateway Extensibility](#payment-gateway-extensibility)
7. [Testing](#testing)
8. [Postman Collection](#postman-collection)
9. [Project Structure](#project-structure)
10. [Notes & Assumptions](#notes--assumptions)

---

## 📦 Requirements

- **Docker** + **Docker Compose**
- PHP 8.5 (via Docker Alpine image)
- SQLite (embedded in the container)

---

## 🚀 Quick Start

### 1. Clone & Setup
```bash
git clone https://github.com/Mina-Sayed/tocaan-payment-api.git
cd tocaan-payment-api
```

### 2. Build & Start with Docker
```bash
docker compose up --build
```

The container will automatically:
- ✅ Install PHP dependencies via Composer
- ✅ Generate `APP_KEY` and `JWT_SECRET` if missing
- ✅ Create SQLite database at `database/database.sqlite`
- ✅ Run all migrations
- ✅ Seed the database (optional)

### 3. Verify Installation
The API will be available at: **`http://localhost:8000`**

Check health:
```bash
curl http://localhost:8000
```

---

## 🔑 Authentication (JWT)

All protected endpoints require a Bearer token in the request header:

```http
Authorization: Bearer <your_jwt_token>
```

**Get a token:**
1. Register: `POST /api/v1/auth/register`
2. Login: `POST /api/v1/auth/login`
3. Use the returned `token` in subsequent requests

---

## 📡 API Endpoints

### Base URL
All endpoints are prefixed with `/api/v1`

### **Auth Endpoints** (Public)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Create new account |
| POST | `/auth/login` | Login & get JWT token |
| POST | `/auth/refresh` | Refresh expired token |
| POST | `/auth/logout` | Logout (invalidate token) |
| GET | `/auth/me` | Get current user info |

### **Order Endpoints** (Protected)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/orders` | Create new order |
| GET | `/orders` | List orders (with filters) |
| GET | `/orders/{id}` | Get order details |
| PUT/PATCH | `/orders/{id}` | Update order |
| DELETE | `/orders/{id}` | Delete order |

**Query Parameters for GET `/orders`:**
```
?status=pending|confirmed|cancelled
?per_page=15
?page=1
```

### **Payment Endpoints** (Protected)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/orders/{id}/payments` | Process payment |
| GET | `/orders/{id}/payments` | Get order payments |
| GET | `/payments` | List all payments |

**Query Parameters for GET `/payments`:**
```
?status=successful|failed|pending
?method=credit_card|paypal
```

---

## 📏 Business Rules

1. **Order Status Flow**: `pending` → `confirmed` → payment processing
2. **Payments**: Can only be processed for orders in `confirmed` status
3. **Order Deletion**: Blocked if any payments exist
4. **Totals**: Always calculated server-side from line items (never trusting client input)
5. **Payment Methods**: Must be configured in `config/payments.php`

---

## 💳 Payment Gateway Extensibility

The system uses the **Strategy Pattern** via a payment contract interface. This allows adding new payment gateways without modifying existing code.

### Architecture
```
App/Contracts/Payments/PaymentGatewayContract (Interface)
    ↓
App/Payments/Gateways/
    ├── CreditCardGateway (implements PaymentGatewayContract)
    ├── PaypalGateway (implements PaymentGatewayContract)
    └── [YourNewGateway] (your new implementation)
    ↓
PaymentGatewayResolver (selects the correct gateway)
```

### How to Add a New Payment Gateway

#### Step 1: Create the Gateway Class
Create `app/Payments/Gateways/StripeGateway.php`:

```php
<?php

namespace App\Payments\Gateways;

use App\Contracts\Payments\PaymentGatewayContract;
use App\DTO\Payments\PaymentGatewayResult;

class StripeGateway implements PaymentGatewayContract
{
    public function key(): string
    {
        return 'stripe';
    }

    public function charge(float $amount, string $currency = 'USD'): PaymentGatewayResult
    {
        // Call Stripe API
        $stripeApiKey = config('payments.stripe.api_key');
        
        try {
            // Your Stripe implementation
            $chargeId = 'ch_' . uniqid();
            
            return PaymentGatewayResult::success(
                transactionId: $chargeId,
                gateway: $this->key(),
                amount: $amount
            );
        } catch (\Exception $e) {
            return PaymentGatewayResult::failed(
                gateway: $this->key(),
                reason: $e->getMessage()
            );
        }
    }
}
```

#### Step 2: Register in Config
Update `config/payments.php`:

```php
'methods' => [
    'credit_card' => \App\Payments\Gateways\CreditCardGateway::class,
    'paypal' => \App\Payments\Gateways\PaypalGateway::class,
    'stripe' => \App\Payments\Gateways\StripeGateway::class,  // Add this
],
```

#### Step 3: Add Environment Variables
Update `.env`:

```env
STRIPE_API_KEY=sk_test_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

#### Step 4: Write Tests
Create `tests/Unit/StripeGatewayTest.php`:

```php
public function test_stripe_gateway_can_charge()
{
    $gateway = new StripeGateway();
    
    $result = $gateway->charge(100.00);
    
    $this->assertTrue($result->isSuccessful());
    $this->assertEquals('stripe', $result->gateway);
}
```

#### Step 5: Use the Gateway
The `PaymentGatewayResolver` will automatically select the right gateway based on the request:

```bash
POST /api/v1/orders/{id}/payments
{
    "method": "stripe",  // PaymentGatewayResolver will use StripeGateway
    "amount": 100.00
}
```

---

## 🧪 Testing

### Run All Tests
```bash
docker compose exec -T api composer test
```

### Run Specific Test Suite
```bash
# Feature tests only
docker compose exec -T api composer exec phpunit tests/Feature

# Unit tests only
docker compose exec -T api composer exec phpunit tests/Unit
```

### Test Coverage
- ✅ Authentication flows
- ✅ Order CRUD operations
- ✅ Payment processing
- ✅ Gateway resolver logic
- ✅ Business rule validation

---

## 📬 Postman Collection

### Import Steps
1. Open **Postman**
2. Click **Import** → **File**
3. Select: `postman/order-payment-api.postman_collection.json`
4. Collection will include:
   - ✅ Auth flows (register, login, refresh)
   - ✅ Order CRUD operations
   - ✅ Payment processing
   - ✅ Error case examples
   - ✅ Pre-configured base URL and variables

### Using the Collection
- Set `base_url` variable: `http://localhost:8000`
- Set `token` variable after login (auto-populated)
- All requests include proper headers and body examples

---

## 📂 Project Structure

```
├── app/
│   ├── Contracts/          # Interfaces (PaymentGatewayContract)
│   ├── DTO/                # Data Transfer Objects
│   ├── Http/
│   │   ├── Controllers/    # API controllers
│   │   └── Requests/       # Form validation requests
│   ├── Models/             # Eloquent models (User, Order, Payment)
│   ├── Payments/
│   │   ├── PaymentGatewayResolver.php    # Gateway selector
│   │   └── Gateways/       # Gateway implementations
│   ├── Services/           # Business logic (PaymentService)
│   └── Providers/          # Service providers
│
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── payments.php        # Payment gateway configuration
│   └── ...
│
├── database/
│   ├── migrations/         # Database schema
│   ├── factories/          # Model factories for testing
│   └── seeders/            # Seeding data
│
├── routes/
│   ├── api.php             # API routes
│   └── web.php             # Web routes
│
├── tests/
│   ├── Feature/            # Integration tests
│   └── Unit/               # Unit tests
│
├── docker-compose.yml      # Docker setup
├── Dockerfile              # PHP container config
└── README.md               # This file
```

---

## 📝 Notes & Assumptions

### Design Decisions
1. **Strategy Pattern**: Payment gateways use the strategy pattern for clean extensibility
2. **JWT Authentication**: Stateless auth using Laravel JWT
3. **DTO Objects**: Payment results wrapped in `PaymentGatewayResult` DTO
4. **Server-side Calculations**: All totals calculated server-side, no client trust
5. **SQLite**: Lightweight, no external DB required for development

### Limitations & Known Issues
- **Simulated Payments**: Currently payment gateways are simulated (not real charges)
- **No Partial Payments**: System processes full order amount only
- **No Webhooks**: Payment webhooks not implemented (for real integrations)
- **Single User Context**: Payments tied to authenticated user's orders only


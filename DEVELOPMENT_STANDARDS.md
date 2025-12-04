# HostForge Development Standards

**Version**: 1.0  
**Last Updated**: 4 december 2025  
**Applies to**: All backend (Laravel) and frontend (Vue.js) development

---

## 🎯 Core Principles

### 1. Code Quality First
- Code is read 10x more than written - prioritize clarity over cleverness
- Every line of code is a liability - keep it simple and maintainable
- No technical debt - refactor as you go
- Boy Scout Rule: Leave code better than you found it

### 2. Test-Driven Development
- Write tests before or alongside implementation
- Minimum 80% code coverage
- Tests are documentation - make them readable
- Fast tests (<5s for unit tests, <30s for feature tests)

### 3. Security by Design
- Never trust user input
- Validate, sanitize, escape
- Principle of least privilege
- Defense in depth

### 4. Performance Matters
- <200ms API response time (p95)
- Database queries: N+1 prevention
- Caching strategy for expensive operations
- Lazy loading for frontend

### 5. Configuration over Hard-coding
- All environment-specific values in config
- Magic strings → constants/enums
- Feature flags for gradual rollouts
- No secrets in code (use .env)

### 6. Resilience & Reliability
- Graceful degradation
- Retry policies for external APIs
- Circuit breakers for failing services
- Idempotent operations where possible

---

## 📁 Project Structure

### Backend (Laravel)

```
app/
├── Actions/                      # Single-purpose executable classes
│   ├── Order/
│   │   ├── CreateOrderAction.php
│   │   └── ApproveOrderAction.php
│   └── ...
├── DataTransferObjects/          # Type-safe data containers
│   ├── OrderData.php
│   └── CustomerData.php
├── Events/                       # Domain events
│   └── Order/
│       └── OrderCreated.php
├── Exceptions/                   # Custom exceptions
│   ├── Domain/
│   └── Integration/
├── Http/
│   ├── Controllers/              # Thin controllers (orchestration only)
│   │   ├── Api/
│   │   └── Admin/
│   ├── Middleware/
│   ├── Requests/                 # Form validation
│   └── Resources/                # API response transformation
├── Jobs/                         # Queue jobs
├── Listeners/                    # Event listeners
├── Models/                       # Eloquent models
├── Repositories/                 # Data access layer
│   ├── Contracts/                # Interfaces
│   └── Eloquent/                 # Implementations
└── Services/                     # Business logic
    ├── Domain/                   # Domain services
    └── Integration/              # External API clients
```

### Frontend (Vue.js)

```
resources/js/
├── src/
│   ├── api/                      # API client layer
│   │   ├── client.ts
│   │   └── modules/
│   │       ├── orders.ts
│   │       └── customers.ts
│   ├── components/               # Reusable components
│   │   ├── ui/                   # Base components (Button, Input, etc.)
│   │   ├── forms/                # Form components
│   │   └── layouts/              # Layout components
│   ├── composables/              # Vue composables (useX pattern)
│   ├── router/                   # Vue Router config
│   ├── stores/                   # Pinia stores
│   ├── types/                    # TypeScript types
│   ├── utils/                    # Utility functions
│   ├── views/                    # Page components
│   │   ├── public/
│   │   └── admin/
│   └── App.vue
└── app.ts
```

---

## 🏗️ Architecture Patterns

### 1. Repository Pattern

**Purpose**: Abstract database access, improve testability

**Example**:

```php
// Contract
namespace App\Repositories\Contracts;

interface OrderRepositoryInterface
{
    public function find(int $id): ?Order;
    public function findByOrderNumber(string $orderNumber): ?Order;
    public function create(OrderData $data): Order;
    public function update(Order $order, OrderData $data): Order;
}

// Implementation
namespace App\Repositories\Eloquent;

class OrderRepository implements OrderRepositoryInterface
{
    public function find(int $id): ?Order
    {
        return Order::with(['customer', 'hostingPackage', 'domains'])->find($id);
    }
    
    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return Order::with(['customer', 'hostingPackage', 'domains'])
            ->where('order_number', $orderNumber)
            ->first();
    }
    
    // ...
}

// Service Provider binding
$this->app->bind(
    OrderRepositoryInterface::class,
    OrderRepository::class
);

// Usage in controller
public function __construct(
    private OrderRepositoryInterface $orderRepository
) {}
```

### 2. Action Classes

**Purpose**: Single Responsibility - one action, one class

**Example**:

```php
namespace App\Actions\Order;

use App\DataTransferObjects\OrderData;
use App\Events\Order\OrderCreated;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

class CreateOrderAction
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {}

    public function execute(OrderData $orderData): Order
    {
        $order = $this->orderRepository->create($orderData);
        
        event(new OrderCreated($order));
        
        return $order;
    }
}
```

### 3. Data Transfer Objects (DTOs)

**Purpose**: Type-safe data transfer between layers

**Example**:

```php
namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

class OrderData extends Data
{
    public function __construct(
        public readonly int $customerId,
        public readonly ?int $hostingPackageId,
        public readonly string $billingCycle,
        public readonly array $domains,
        public readonly string $status = 'pending',
    ) {}
    
    public static function fromRequest(CreateOrderRequest $request): self
    {
        return new self(
            customerId: $request->input('customer_id'),
            hostingPackageId: $request->input('hosting_package_id'),
            billingCycle: $request->input('billing_cycle'),
            domains: $request->input('domains', []),
        );
    }
}
```

### 4. Events & Listeners

**Purpose**: Decouple side-effects from core business logic

**Example**:

```php
// Event
namespace App\Events\Order;

class OrderCreated
{
    public function __construct(
        public readonly Order $order
    ) {}
}

// Listener
namespace App\Listeners\Order;

class SendOrderConfirmationEmail
{
    public function handle(OrderCreated $event): void
    {
        Mail::to($event->order->customer->email)
            ->send(new OrderConfirmationMail($event->order));
    }
}

// EventServiceProvider
protected $listen = [
    OrderCreated::class => [
        SendOrderConfirmationEmail::class,
        NotifyAdminOfNewOrder::class,
    ],
];
```

### 5. API Resources

**Purpose**: Consistent API response formatting

**Example**:

```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'total' => $this->total,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'hosting_package' => new HostingPackageResource($this->whenLoaded('hostingPackage')),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

// Controller usage
return OrderResource::make($order);
return OrderResource::collection($orders);
```

---

## 🧪 Testing Standards

### Unit Tests

**Rules**:
- Test one thing per test
- No database hits (use mocks)
- Fast (<100ms per test)
- Descriptive test names

**Example**:

```php
namespace Tests\Unit\Actions;

use App\Actions\Order\CreateOrderAction;
use App\DataTransferObjects\OrderData;
use App\Events\Order\OrderCreated;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateOrderActionTest extends TestCase
{
    /** @test */
    public function it_creates_an_order_and_dispatches_event(): void
    {
        // Arrange
        Event::fake();
        
        $orderData = new OrderData(
            customerId: 1,
            hostingPackageId: 2,
            billingCycle: 'yearly',
            domains: ['example.com'],
        );
        
        $repository = $this->mock(OrderRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->with($orderData)
            ->andReturn($expectedOrder = new Order());
        
        $action = new CreateOrderAction($repository);
        
        // Act
        $result = $action->execute($orderData);
        
        // Assert
        $this->assertSame($expectedOrder, $result);
        Event::assertDispatched(OrderCreated::class);
    }
}
```

### Feature Tests

**Rules**:
- Test complete user flows
- Use database transactions
- Test API responses completely

**Example**:

```php
namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\HostingPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_order_with_valid_data(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $package = HostingPackage::factory()->create();
        
        $payload = [
            'customer_id' => $customer->id,
            'hosting_package_id' => $package->id,
            'billing_cycle' => 'yearly',
            'domains' => [
                ['name' => 'example.com', 'register_domain' => true],
            ],
        ];
        
        // Act
        $response = $this->postJson('/api/v1/orders', $payload);
        
        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'order_number',
                    'status',
                    'total',
                ],
            ]);
        
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'hosting_package_id' => $package->id,
            'status' => 'pending',
        ]);
    }
}
```

### Integration Tests

**Rules**:
- Test external API integrations
- Use mocks/fakes for external services
- Test error scenarios

**Example**:

```php
namespace Tests\Integration\Services;

use App\Services\Integration\PleskService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PleskServiceTest extends TestCase
{
    /** @test */
    public function it_creates_plesk_customer_successfully(): void
    {
        // Arrange
        Http::fake([
            '*/api/v2/customers' => Http::response(['id' => 123], 201),
        ]);
        
        $service = app(PleskService::class);
        
        // Act
        $result = $service->createCustomer([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);
        
        // Assert
        $this->assertEquals(123, $result['id']);
        Http::assertSent(function ($request) {
            return $request->url() === config('services.plesk.url') . '/api/v2/customers'
                && $request['email'] === 'test@example.com';
        });
    }
}
```

---

## 📝 Code Style

### PHP (PSR-12 + Laravel)

**Use Laravel Pint for automatic formatting**:

```bash
./vendor/bin/pint
```

**Rules**:
- Type hints everywhere (strict types enabled)
- Readonly properties when possible
- Constructor property promotion
- Named arguments for clarity
- Early returns over nested ifs

**Example**:

```php
<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DataTransferObjects\OrderData;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

final class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {}

    public function createOrder(OrderData $orderData): Order
    {
        // Early return for validation
        if ($orderData->hostingPackageId === null && empty($orderData->domains)) {
            throw new InvalidOrderException('Order must have hosting or domains');
        }
        
        return $this->orderRepository->create($orderData);
    }
}
```

### TypeScript/Vue.js

**Use ESLint + Prettier**:

```bash
npm run lint
npm run format
```

**Rules**:
- Composition API only (no Options API)
- TypeScript for all new code
- Props with type definitions
- Composables for reusable logic

**Example**:

```typescript
// Component
<script setup lang="ts">
import { ref, computed } from 'vue'
import { useOrders } from '@/composables/useOrders'
import type { Order } from '@/types/models'

const props = defineProps<{
  customerId: number
}>()

const emit = defineEmits<{
  orderCreated: [order: Order]
}>()

const { orders, loading, createOrder } = useOrders()

const customerOrders = computed(() =>
  orders.value.filter(order => order.customer_id === props.customerId)
)

const handleSubmit = async (data: OrderFormData) => {
  const order = await createOrder(data)
  emit('orderCreated', order)
}
</script>

<template>
  <div class="order-list">
    <OrderItem
      v-for="order in customerOrders"
      :key="order.id"
      :order="order"
    />
  </div>
</template>
```

```typescript
// Composable
import { ref } from 'vue'
import { ordersApi } from '@/api/orders'
import type { Order, CreateOrderPayload } from '@/types/models'

export function useOrders() {
  const orders = ref<Order[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchOrders = async () => {
    loading.value = true
    error.value = null
    
    try {
      orders.value = await ordersApi.getAll()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch orders'
    } finally {
      loading.value = false
    }
  }

  const createOrder = async (payload: CreateOrderPayload): Promise<Order> => {
    loading.value = true
    
    try {
      const order = await ordersApi.create(payload)
      orders.value.push(order)
      return order
    } finally {
      loading.value = false
    }
---

## ✅ Code Quality Checklist

Before submitting any PR, verify:

### Configuration & Constants
- [ ] **No magic strings** - Use constants/enums for repeated values
- [ ] **Config files** - All environment values in `config/` directory
- [ ] **Feature flags** - Configurable feature toggles
- [ ] **No hardcoded URLs** - Use `config('app.url')` or route helpers

**Example**:
```php
// ❌ Bad - Magic strings
if ($order->status === 'pending') {
    // ...
}

// ✅ Good - Constants/Enums
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
}

if ($order->status === OrderStatus::PENDING) {
    // ...
}

// ❌ Bad - Hardcoded value
$timeout = 30;

// ✅ Good - Configurable
$timeout = config('services.plesk.timeout', 30);
```

### Input Validation
- [ ] **All user input validated** - Form Requests for API, frontend validation
- [ ] **Type hints everywhere** - Strict types enabled
- [ ] **Database constraints** - Foreign keys, unique constraints, not null
- [ ] **Sanitization** - Clean HTML input with purifier

### Error Handling
- [ ] **Try-catch blocks** - Around external API calls
- [ ] **Custom exceptions** - Domain-specific exception classes
- [ ] **Proper logging** - Context included in all logs
- [ ] **User-friendly messages** - Don't expose internal errors to users
- [ ] **Failed job handling** - Retry logic + dead letter queue

**Example**:
```php
// ❌ Bad - Generic exception
throw new Exception('Something went wrong');

// ✅ Good - Specific exception with context
throw new PleskProvisioningException(
    "Failed to create Plesk subscription for order {$order->order_number}",
    ['order_id' => $order->id, 'plesk_response' => $response]
);
```

### Resource Management
- [ ] **Close database connections** - Use try-finally or use automatic cleanup
- [ ] **File handles closed** - Use `Storage::` facade or close manually
- [ ] **Memory management** - Chunk large datasets, unset when done
- [ ] **HTTP clients** - Reuse Guzzle clients, set timeouts

### Thread Safety & Concurrency
- [ ] **Atomic operations** - Use database transactions
- [ ] **Race condition prevention** - Locks for critical sections
- [ ] **Job uniqueness** - `ShouldBeUnique` interface for jobs
- [ ] **Cache race conditions** - Use `Cache::lock()` for writes

**Example**:
```php
// ✅ Good - Atomic operation with lock
use Illuminate\Support\Facades\Cache;

$lock = Cache::lock('order-processing-' . $order->id, 10);

if ($lock->get()) {
    try {
        // Process order (only one worker at a time)
        $this->processOrder($order);
    } finally {
        $lock->release();
    }
}
```

### Documentation
- [ ] **PHPDoc for public methods** - Include @param, @return, @throws
- [ ] **README for complex modules** - Purpose, usage, examples
- [ ] **Inline comments** - Only for non-obvious business logic
- [ ] **API documentation** - OpenAPI spec updated

### Security Checks
- [ ] **XSS prevention** - Escape all output, use `{{ }}` in Blade
- [ ] **CSRF protection** - Enabled for all state-changing requests
- [ ] **SQL injection** - Use Eloquent/Query Builder with bindings
- [ ] **Mass assignment** - Define `$fillable` or `$guarded` on models
- [ ] **Authorization** - Policies for all resources
- [ ] **Rate limiting** - Applied to public endpoints
- [ ] **Input length limits** - Prevent DoS via large payloads
- [ ] **Idempotency keys** - For payment and provisioning operations

### Performance
- [ ] **N+1 queries** - Use eager loading (`with()`)
- [ ] **Indexes** - On foreign keys and frequently queried columns
- [ ] **Pagination** - For large result sets
- [ ] **Caching** - Expensive queries cached
- [ ] **Queue jobs** - Long-running tasks in background
- [ ] **Asset optimization** - Minified JS/CSS, lazy loading images

---

## 🔒 Security Standards
    orders,
    loading,
    error,
    fetchOrders,
    createOrder,
  }
}
```

---

## 🔒 Security Standards

### Input Validation

**Always validate at multiple layers**:

1. **Frontend**: User experience (immediate feedback)
2. **Form Request**: Server-side validation (Laravel)
3. **Service Layer**: Business rules validation

**Example**:

```php
namespace App\Http\Requests;

class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'hosting_package_id' => ['nullable', 'exists:hosting_packages,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'domains' => ['required', 'array', 'min:1'],
            'domains.*.name' => ['required', 'string', 'max:255'],
            'domains.*.register_domain' => ['required', 'boolean'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'customer_id.exists' => 'Klant niet gevonden',
            'billing_cycle.in' => 'Ongeldige factureringsperiode',
            'domains.min' => 'Minimaal één domein is verplicht',
        ];
    }
}
```

### SQL Injection Prevention

**Use Eloquent or Query Builder (never raw queries)**:

```php
// ✅ Good - Eloquent
Order::where('customer_id', $customerId)->get();

// ✅ Good - Query Builder with bindings
DB::table('orders')
    ->where('customer_id', $customerId)
    ->get();

// ❌ Bad - Raw query (SQL injection risk)
DB::select("SELECT * FROM orders WHERE customer_id = $customerId");

// ✅ Good - Raw query with bindings (if absolutely necessary)
DB::select("SELECT * FROM orders WHERE customer_id = ?", [$customerId]);
```

### XSS Prevention

**Always escape output in Blade**:

```blade
{{-- ✅ Good - Auto-escaped --}}
{{ $user->name }}

{{-- ❌ Bad - No escaping --}}
{!! $user->name !!}

{{-- ✅ Good - HTML allowed (use purifier) --}}
{!! clean($user->bio) !!}
```

### Authentication & Authorization

**Use Laravel Sanctum + Policies**:

```php
// Policy
namespace App\Policies;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->id === $order->customer->user_id;
    }
    
    public function approve(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}

// Controller
public function show(Order $order): JsonResponse
{
    $this->authorize('view', $order);
    
    return OrderResource::make($order);
}
```

---

## 🚀 Performance Standards

### Database Optimization

**Prevent N+1 queries**:

```php
// ❌ Bad - N+1 query
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->customer->name; // N queries
}

// ✅ Good - Eager loading
$orders = Order::with('customer')->get();
foreach ($orders as $order) {
    echo $order->customer->name; // 1 query
}
```

**Use indexes**:

```php
Schema::table('orders', function (Blueprint $table) {
    $table->index('customer_id');
    $table->index('status');
    $table->index(['customer_id', 'status']); // Composite index
    $table->unique('order_number');
});
```

### Caching Strategy

**Cache expensive queries**:

```php
use Illuminate\Support\Facades\Cache;

// Cache for 1 hour
$packages = Cache::remember('hosting_packages', 3600, function () {
    return HostingPackage::where('active', true)->get();
});

// Invalidate cache when updated
public function updatePackage(HostingPackage $package, array $data): void
{
    $package->update($data);
    Cache::forget('hosting_packages');
}
```

### Queue Long-Running Tasks

**Never block HTTP requests**:

```php
// ❌ Bad - Synchronous (blocks request)
public function approve(Order $order): JsonResponse
{
    $this->pleskService->provisionHosting($order); // Takes 30s
    $this->openProviderService->registerDomain($order); // Takes 10s
    
    return response()->json(['message' => 'Order approved']);
}

// ✅ Good - Asynchronous (immediate response)
public function approve(Order $order): JsonResponse
{
    $order->update(['status' => 'processing']);
    
    ProvisionHostingJob::dispatch($order);
    RegisterDomainJob::dispatch($order);
    
    return response()->json(['message' => 'Order is being processed']);
}
```

---

## 🔄 Resilience Patterns

### Retry Policies for External APIs

**Use exponential backoff for transient failures**:

```php
use Illuminate\Support\Facades\Http;

// ✅ Good - Retry with backoff
$response = Http::retry(3, 100, function ($exception, $request) {
    // Only retry on 5xx errors or network issues
    return $exception instanceof ConnectionException
        || ($exception instanceof RequestException 
            && $exception->response->status() >= 500);
})
->timeout(10)
->post('https://api.plesk.com/customers', $data);

// ✅ Better - Custom retry in service
namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class PleskService
{
    private const MAX_RETRIES = 3;
    private const INITIAL_BACKOFF_MS = 100;
    
    public function createCustomer(array $data): array
    {
        $attempt = 0;
        
        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = Http::timeout(10)
                    ->post($this->getApiUrl() . '/customers', $data);
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                // Don't retry client errors (4xx)
                if ($response->status() < 500) {
                    throw new PleskApiException(
                        "Plesk API error: {$response->body()}",
                        $response->status()
                    );
                }
                
                // Server error, retry
                $attempt++;
                
            } catch (ConnectionException $e) {
                $attempt++;
                if ($attempt >= self::MAX_RETRIES) {
                    throw new PleskApiException('Failed to connect to Plesk API', 0, $e);
                }
            }
            
            // Exponential backoff: 100ms, 200ms, 400ms
            usleep(self::INITIAL_BACKOFF_MS * (2 ** $attempt) * 1000);
        }
        
        throw new PleskApiException('Max retries exceeded for Plesk API');
    }
}
```

### Circuit Breaker Pattern

**Prevent cascading failures**:

```php
use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    private const FAILURE_THRESHOLD = 5;
    private const TIMEOUT_SECONDS = 60;
    
    public function call(string $service, callable $callback): mixed
    {
        $cacheKey = "circuit_breaker:{$service}";
        
        // Check if circuit is open
        if (Cache::get($cacheKey . ':open')) {
            throw new ServiceUnavailableException("Circuit breaker open for {$service}");
        }
        
        try {
            $result = $callback();
            
            // Reset failure count on success
            Cache::forget($cacheKey . ':failures');
            
            return $result;
            
        } catch (\Exception $e) {
            // Increment failure count
            $failures = Cache::increment($cacheKey . ':failures');
            
            if ($failures >= self::FAILURE_THRESHOLD) {
                // Open circuit
                Cache::put($cacheKey . ':open', true, self::TIMEOUT_SECONDS);
                Cache::forget($cacheKey . ':failures');
            }
            
            throw $e;
        }
    }
}

// Usage
$circuitBreaker = new CircuitBreaker();

try {
    $result = $circuitBreaker->call('plesk', function () use ($order) {
        return $this->pleskService->createCustomer($order->customer);
    });
} catch (ServiceUnavailableException $e) {
    // Plesk is down, queue for later
    ProvisionHostingJob::dispatch($order)->delay(now()->addMinutes(5));
}
```

### Idempotency Keys

**Prevent duplicate operations**:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class EnsureIdempotency
{
    public function handle($request, Closure $next)
    {
        // Only for state-changing requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }
        
        $idempotencyKey = $request->header('Idempotency-Key');
        
        if (!$idempotencyKey) {
            return response()->json([
                'error' => 'Idempotency-Key header required'
            ], 400);
        }
        
        $cacheKey = "idempotency:{$idempotencyKey}";
        
        // Check if request was already processed
        if ($cachedResponse = Cache::get($cacheKey)) {
            return response()->json($cachedResponse['body'], $cachedResponse['status']);
        }
        
        // Process request
        $response = $next($request);
        
        // Cache response for 24 hours
        Cache::put($cacheKey, [
            'body' => $response->getData(),
            'status' => $response->status(),
        ], now()->addHours(24));
        
        return $response;
    }
}

// Apply to routes
Route::middleware(['auth:sanctum', 'idempotency'])->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
});
```

### Graceful Degradation

**Continue operating with reduced functionality**:

```php
namespace App\Services\Domain;

class OrderService
{
    public function __construct(
        private PleskService $pleskService,
        private MoneybirdService $moneybirdService,
    ) {}
    
    public function approveOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => OrderStatus::PROCESSING]);
            
            // Critical: Always provision hosting
            try {
                $this->provisionHosting($order);
            } catch (\Exception $e) {
                // Can't continue without hosting
                throw new ProvisioningFailedException('Failed to provision hosting', 0, $e);
            }
            
            // Non-critical: Try to sync to Moneybird
            try {
                $this->syncToMoneybird($order);
            } catch (\Exception $e) {
                // Log but don't fail the order
                Log::warning('Failed to sync order to Moneybird', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                
                // Queue for retry later
                SyncCustomerToMoneybirdJob::dispatch($order)->delay(now()->addMinutes(5));
            }
            
            $order->update(['status' => OrderStatus::ACTIVE]);
        });
    }
}
```

---

## 📊 Logging Best Practices

### Log Levels

**Use appropriate log levels**:

```php
use Illuminate\Support\Facades\Log;

// DEBUG - Detailed information for diagnosing problems (dev only)
Log::debug('Processing order', ['order_id' => $order->id, 'items' => $items]);

// INFO - Interesting events (user logged in, order created)
Log::info('Order created', ['order_number' => $order->order_number]);

// NOTICE - Normal but significant events
Log::notice('Customer approved', ['customer_id' => $customer->id]);

// WARNING - Exceptional occurrences that are not errors
Log::warning('Plesk API slow response', ['duration' => $duration]);

// ERROR - Runtime errors that don't require immediate action
Log::error('Failed to send email', ['order_id' => $order->id, 'error' => $e->getMessage()]);

// CRITICAL - Critical conditions (external service down)
Log::critical('Plesk API unreachable', ['attempts' => 3]);

// ALERT - Action must be taken immediately
Log::alert('Database connection lost');

// EMERGENCY - System is unusable
Log::emergency('Application crash');
```

### Structured Logging

**Include context for debugging**:

```php
// ❌ Bad - No context
Log::error('Order processing failed');

// ✅ Good - With context
Log::error('Order processing failed', [
    'order_id' => $order->id,
    'order_number' => $order->order_number,
    'customer_id' => $order->customer_id,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
    'request_id' => request()->header('X-Request-ID'),
]);

// ✅ Better - Use log context
Log::withContext([
    'order_id' => $order->id,
    'order_number' => $order->order_number,
])->error('Order processing failed', [
    'error' => $e->getMessage(),
]);
```

### Performance Logging

**Track slow operations**:

```php
use Illuminate\Support\Facades\Log;

class PerformanceMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000; // Convert to ms
        
        // Log slow requests
        if ($duration > 1000) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);
        }
        
        return $response;
    }
}
```

---

## 🎯 Result Objects vs Exceptions

### When to Use Result Objects

**For expected failures (business logic)**:

```php
namespace App\ValueObjects;

class Result
{
    private function __construct(
        private readonly bool $success,
        private readonly mixed $value = null,
        private readonly ?string $error = null,
    ) {}
    
    public static function success(mixed $value = null): self
    {
        return new self(true, $value);
    }
    
    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }
    
    public function isSuccess(): bool
    {
        return $this->success;
    }
    
    public function isFailure(): bool
    {
        return !$this->success;
    }
    
    public function getValue(): mixed
    {
        if ($this->isFailure()) {
            throw new \LogicException('Cannot get value from failed result');
        }
        
        return $this->value;
    }
    
    public function getError(): ?string
    {
        return $this->error;
    }
}

// Usage
class DomainCheckService
{
    public function checkAvailability(string $domain): Result
    {
        try {
            $available = $this->openProviderService->checkDomain($domain);
            
            if (!$available) {
                return Result::failure('Domain is not available');
            }
            
            return Result::success(['domain' => $domain, 'available' => true]);
            
        } catch (\Exception $e) {
            return Result::failure('Failed to check domain availability');
        }
    }
}

// Controller
public function check(Request $request): JsonResponse
{
    $result = $this->domainCheckService->checkAvailability($request->input('domain'));
    
    if ($result->isFailure()) {
        return response()->json([
            'success' => false,
            'message' => $result->getError(),
        ], 400);
    }
    
    return response()->json([
        'success' => true,
        'data' => $result->getValue(),
    ]);
}
```

### When to Use Exceptions

**For unexpected errors (infrastructure/system)**:

```php
// Use exceptions for:
// - Database connection failures
// - External API unavailable
// - File system errors
// - Configuration errors
// - Programming errors (null reference, type mismatch)

class PleskService
{
    public function createCustomer(array $data): array
    {
        try {
            $response = Http::post($this->apiUrl . '/customers', $data);
            
            if ($response->failed()) {
                throw new PleskApiException(
                    "Plesk API returned error: {$response->body()}",
                    $response->status()
                );
            }
            
            return $response->json();
            
        } catch (ConnectionException $e) {
            // Unexpected - infrastructure failure
            throw new PleskApiException('Cannot connect to Plesk API', 0, $e);
        }
    }
}
```

---

## 📚 Documentation Standards

### Code Comments

**When to comment**:
- Complex algorithms
- Business rules
- Non-obvious decisions
- TODOs with context

**When NOT to comment**:
- Obvious code (let code speak)
- Outdated comments
- Commented-out code (use git)

**Example**:

```php
// ❌ Bad - Obvious
// Get the order
$order = Order::find($id);

// ✅ Good - Explains business rule
// Orders older than 30 days are archived to reduce query load on main table
if ($order->created_at->diffInDays() > 30) {
    $this->archiveOrder($order);
}

// ✅ Good - TODO with context
// TODO: Implement retry logic when Plesk API returns 503
// Ticket: #BILL-123
// This happens during Plesk maintenance windows (usually 2-4am UTC)
```

### PHPDoc

**Required for**:
- Public methods
- Complex return types
- Array structures

**Example**:

```php
/**
 * Create a new order with hosting package and domains.
 *
 * @param  OrderData  $orderData  The order information
 * @return Order The created order
 *
 * @throws InvalidOrderException If order data is invalid
 * @throws InsufficientCreditsException If customer has insufficient credits
 */
public function createOrder(OrderData $orderData): Order
{
    // ...
}
```

### README per module

**Create README.md for complex modules**:

```
app/Services/Integration/Plesk/README.md
```

**Include**:
- Purpose
- Configuration
- Usage examples
- Common issues

---

## 🔄 Git Workflow

### Branch Naming

```
feature/BILL-123-add-payment-integration
bugfix/BILL-456-fix-domain-check
hotfix/critical-billing-bug
refactor/BILL-789-extract-order-service
```

### Commit Messages (Conventional Commits)

```
feat(orders): add ability to approve multiple orders at once
fix(plesk): handle 503 errors with retry logic
refactor(repositories): extract order repository interface
docs(api): update authentication endpoints
test(orders): add feature test for order creation
chore(deps): update laravel to 11.x
```

### Pull Request Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests added
- [ ] Feature tests added
- [ ] Manual testing performed

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review performed
- [ ] Comments added for complex code
- [ ] Documentation updated
- [ ] No new warnings
- [ ] Tests pass locally
```

---

## ✅ Definition of Done

A task is considered DONE when:

- ✅ Code is written and follows standards
- ✅ Unit tests written (80%+ coverage)
- ✅ Feature tests written for user flows
- ✅ Code review approved by peer
- ✅ Documentation updated (README, API docs)
- ✅ No linting errors
- ✅ PHPStan level 5+ passes
- ✅ Manual testing performed
- ✅ Deployed to staging
- ✅ Stakeholder approved (if needed)
- ✅ Merged to main branch

---

## 🛠️ Tools Configuration

### PHPStan (phpstan.neon)

```neon
parameters:
    level: 5
    paths:
        - app
    excludePaths:
        - app/Console/Kernel.php
    checkMissingIterableValueType: false
```

### Pint (pint.json)

```json
{
    "preset": "laravel",
    "rules": {
        "simplified_null_return": true,
        "braces": false,
        "new_with_braces": {
            "anonymous_class": false,
            "named_class": false
        }
    }
}
```

### ESLint (.eslintrc.js)

```javascript
module.exports = {
  extends: [
    'plugin:vue/vue3-recommended',
    '@vue/typescript/recommended',
    'prettier'
  ],
  rules: {
    'vue/multi-word-component-names': 'off',
    '@typescript-eslint/explicit-module-boundary-types': 'off'
  }
}
```

---

## 📞 Support & Questions

- **Architecture Questions**: Review this document + ROADMAP.md
- **Code Review**: All PRs require approval
- **Standards Updates**: Propose via PR to this document

---

**Approved By**: [To be filled]  
**Last Review**: 4 december 2025  
**Next Review**: Monthly

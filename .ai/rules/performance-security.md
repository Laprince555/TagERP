---
name: performance-security
description: Performance and security are non-negotiable priorities - optimize aggressively
metadata:
  type: project
  scope: "app/**,Modules/**"
  priority: critical
---

# Performance & Security Priority Rule

**Applies to:** All PHP code, queries, API endpoints, and business logic

## The Rule

**Performance and Security are FIRST-CLASS priorities, not afterthoughts.**

Every implementation must:
1. ✓ Minimize database queries (N+1 prevention)
2. ✓ Reduce HTTP requests and API calls
3. ✓ Implement security at the code level
4. ✓ Cache aggressively where appropriate
5. ✓ Validate and sanitize all inputs
6. ✓ Optimize response payloads

---

## 1. Database Performance (Critical)

### ✓ DO

```php
// Eager load relationships
$invoices = Invoice::with(['customer', 'items', 'payments'])
    ->where('status', 'paid')
    ->get();

// Use select() to fetch only needed columns
$customers = Customer::select('id', 'name', 'email')
    ->where('active', true)
    ->get();

// Chunk large datasets
Invoice::where('status', 'draft')
    ->chunk(500, function ($invoices) {
        // Process $invoices
    });

// Index frequently queried columns
// In migration: $table->index(['status', 'created_at']);

// Use database-level computed columns when possible
// Instead of calculating in PHP loops
```

### ❌ DON'T

```php
// N+1 Query Problem
$invoices = Invoice::all();
foreach ($invoices as $invoice) {
    echo $invoice->customer->name; // Triggers query per invoice
}

// Fetching unnecessary columns
$customers = Customer::get(); // Gets ALL columns including large text fields

// Sequential queries when one could work
$user = User::find(1);
$roles = Role::whereIn('id', $user->role_ids)->get();
// Better: $user->load('roles');
```

---

## 2. Request & API Optimization

### ✓ DO

```php
// Return only required fields in API responses
return response()->json([
    'id' => $invoice->id,
    'number' => $invoice->number,
    'total' => $invoice->total,
    'status' => $invoice->status,
    // NOT: $invoice->toArray() - returns everything
]);

// Use pagination to limit response size
Invoice::where('status', 'paid')
    ->paginate(50); // Not get() or all()

// Cache API responses where appropriate
$customers = cache()->remember('active_customers', 3600, function () {
    return Customer::where('active', true)->get();
});

// Batch process instead of loop + query
$updatedUsers = User::whereIn('id', $userIds)->update(['status' => 'active']);
// Instead of: foreach($userIds) { User::find($id)->update(...) }
```

### ❌ DON'T

```php
// Returning entire models with all columns
return Invoice::all(); // Includes sensitive data potentially

// Unbounded queries
return Customer::get(); // Could be millions of rows

// Sequential API calls in loops
foreach ($orders as $order) {
    $customer = $this->apiClient->getCustomer($order->customer_id); // N requests
}

// Loading full objects when only ID is needed
$invoiceIds = Invoice::with('customer', 'items')->get()->pluck('id');
// Just: $invoiceIds = Invoice::pluck('id');
```

---

## 3. Security (Non-Negotiable)

### ✓ DO

```php
// Validate ALL user input
$validated = $request->validate([
    'email' => 'required|email|unique:users',
    'name' => 'required|string|max:255',
    'password' => 'required|min:8|confirmed',
]);

// Use parameterized queries (Eloquent does this automatically)
User::where('email', $email)->first();

// Hash passwords ALWAYS
Hash::make($password); // Not md5, not plain text

// Authorize before action
$this->authorize('update', $invoice);

// Sanitize output in views
{{ $user->name }} // Blade auto-escapes
{!! $html !!} // Only for trusted HTML

// Use CSRF tokens
{{ csrf_field() }}

// Rate limit sensitive endpoints
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

// Log security-relevant actions
activity('invoice_paid')
    ->performedOn($invoice)
    ->withProperties(['previous_status' => 'draft'])
    ->log();
```

### ❌ DON'T

```php
// Trust user input
$user = User::where('id', request('user_id'))->first(); // No validation

// Store passwords in plain text or with weak hashing
$user->password = md5($password); // NEVER

// SQL injection risk
User::whereRaw("email = '".$email."'"); // SQL injection!

// Exposing sensitive data in responses
return User::all(); // Includes password_hash, tokens, etc.

// Missing authorization
if ($invoice->id == request('invoice_id')) { // No auth check
    $invoice->delete();
}

// Logging sensitive data
Log::info('User login', $request->all()); // Logs password!

// No rate limiting on sensitive operations
// Anyone can brute-force login endpoint
```

---

## 4. Caching Strategy

### ✓ DO

```php
// Cache frequently accessed, rarely-changing data
$settings = cache()->remember('app_settings', 3600, function () {
    return Setting::all();
});

// Cache permission/role checks
$hasPermission = auth()->user()->can('delete_invoice');
// (Laravel caches permissions internally with Spatie)

// Cache expensive computations
$reportData = cache()->remember(
    "report_summary_".date('Y-m-d'),
    86400, // 24 hours
    function () {
        return expensive_report_calculation();
    }
);

// Cache database queries
$activeVendors = cache()->remember('vendors_active', 3600, function () {
    return Vendor::where('status', 'active')->get();
});

// Invalidate cache on data changes
cache()->forget('vendors_active'); // On vendor update/create
```

### ❌ DON'T

```php
// Caching user-specific data that should always be fresh
cache()->put('user_'.$userId.'_balance', $balance);

// Over-caching with long TTL (stale data)
cache()->remember('invoice_'.$id, 604800, function () { // 7 days!
    return Invoice::find($id);
});

// Forgetting to invalidate cache
// User updates status but cache still shows old status

// Caching large objects unnecessarily
cache()->remember('all_customers_with_history', ...); // 1000s of objects
```

---

## 5. Code Efficiency

### ✓ DO

```php
// Use collections methods for efficiency
$paid = $invoices->where('status', 'paid');
$total = $invoices->sum('amount');

// Short-circuit conditions
if (! $user->hasRole('admin')) return;
// Instead of: if ($user->hasRole('admin')) { ... }

// Use immutable operations when possible
$updated = $invoice->update(['status' => 'paid']); // 1 query
// Not: load → modify → save (2-3 queries)

// Leverage database calculations
$stats = Invoice::selectRaw('
    COUNT(*) as total_invoices,
    SUM(amount) as total_amount,
    AVG(amount) as avg_amount
')->where('status', 'paid')->first();
```

### ❌ DON'T

```php
// Looping to calculate when DB can do it
$total = 0;
foreach ($invoices as $invoice) {
    $total += $invoice->amount; // Inefficient
}

// Multiple queries when one would suffice
$invoice = Invoice::find($id);
$customer = Customer::find($invoice->customer_id);
// Better: $invoice->load('customer');

// Unnecessary object creation
$invoices->filter(function ($inv) {
    return $inv->status === 'paid';
})->values(); // Creates new collection each time
```

---

## Priority Decision Tree

When choosing between features and performance/security:

```
Performance & Security Required? 
├─ YES (99% of cases)
│  └─ Implement with optimization
└─ NO (0.1% - extraordinary cases)
   └─ Document why as code comment
```

**Example Comment:**
```php
// EXCEPTION: Intentionally not caching this endpoint result
// because it must always show real-time stock levels for
// warehouse accuracy. Cost: 2 DB queries per request.
// Monitor if this becomes a bottleneck.
```

---

## Verification Checklist

Before committing code, verify:

- [ ] No N+1 queries (use `php artisan debugbar` or similar)
- [ ] Pagination used for large datasets
- [ ] All user input validated
- [ ] Sensitive data not logged/exposed in responses
- [ ] Database indexes on frequently-queried columns
- [ ] Eager loading used for relationships
- [ ] Appropriate caching implemented
- [ ] Rate limiting on sensitive endpoints
- [ ] No SQL injection risks (use Eloquent)
- [ ] Response payload is minimal (only needed fields)

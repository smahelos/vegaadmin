---
mode: 'agent'
description: 'PHP 8.2+ and Laravel 12 coding standards and best practices'
---

# Coding Standards & Best Practices

## PHP 8.2+ Type System
**Always use strict typing and explicit type declarations:**

### Return Types
```php
// ✅ ALWAYS do this
public function getName(): string
public function getUsers(): array
public function findUser(int $id): ?User
public function isActive(): bool
public function getCount(): int
public function getPrice(): float
public function process(): void

// ❌ NEVER do this
public function getName()
public function getUsers()
public function findUser($id)
```

### Parameter Types
```php
// ✅ ALWAYS do this
public function updateUser(int $id, string $name, ?string $email = null): User
public function calculateTotal(array $items, float $tax = 0.0): float
public function setConfig(array $config): void

// ❌ NEVER do this
public function updateUser($id, $name, $email = null)
public function calculateTotal($items, $tax = 0.0)
```

### Property Types
```php
// ✅ ALWAYS do this
protected string $name;
protected int $count;
protected ?User $user = null;
protected array $items = [];
protected bool $isActive = false;

// ❌ NEVER do this
protected $name;
protected $count;
protected $user;
```

### Union Types (PHP 8.0+)
```php
// ✅ Use when appropriate
public function process(string|int $id): User|null
public function getValue(): string|float|null
```

### Intersection Types (PHP 8.1+)
```php
// ✅ Use for interfaces
public function handle(Countable&Iterator $collection): void
```

## Laravel Specific Standards

### Model Methods
```php
// ✅ Always type relationships
public function posts(): HasMany
public function user(): BelongsTo
public function tags(): BelongsToMany

// ✅ Always type accessors/mutators
protected function firstName(): Attribute
{
    return Attribute::make(
        get: fn (string $value): string => ucfirst($value),
        set: fn (string $value): string => strtolower($value),
    );
}

// ✅ Always type scopes
public function scopeActive(Builder $query): Builder
public function scopeByName(Builder $query, string $name): Builder
```

### Request Classes
```php
// ✅ Always type validation methods
public function authorize(): bool
public function rules(): array
public function messages(): array
public function attributes(): array
protected function prepareForValidation(): void
```

### Controllers
```php
// ✅ Always type controller methods
public function index(): View
public function store(UserRequest $request): RedirectResponse
public function show(User $user): View
public function destroy(User $user): JsonResponse
```

### Services & Repositories
```php
// ✅ Always type service methods
public function createUser(array $data): User
public function updateUser(User $user, array $data): User
public function deleteUser(User $user): bool
public function getUsersByRole(string $role): Collection
```

## Documentation Standards

### PHPDoc Comments
```php
/**
 * Create a new user with the given data.
 *
 * @param array{name: string, email: string, password: string} $data
 * @return User The created user instance
 * @throws ValidationException When data is invalid
 * @throws DatabaseException When database operation fails
 */
public function createUser(array $data): User
{
    // Implementation
}
```

### Complex Arrays
```php
/**
 * @param array{
 *     name: string,
 *     email: string,
 *     roles: string[],
 *     metadata?: array<string, mixed>
 * } $userData
 */
public function processUserData(array $userData): User
```

## Error Handling

### Exception Types
```php
// ✅ Always catch specific exceptions
try {
    $user = $this->userService->create($data);
} catch (ValidationException $e) {
    return response()->json(['errors' => $e->errors()], 422);
} catch (DatabaseException $e) {
    Log::error('Database error: ' . $e->getMessage());
    return response()->json(['error' => 'Internal server error'], 500);
}
```

### Custom Exceptions
```php
// ✅ Always type custom exceptions
class UserNotFoundException extends Exception
{
    public function __construct(int $userId)
    {
        parent::__construct("User with ID {$userId} not found");
    }
}
```

## Variable Naming

### Use Descriptive Names
```php
// ✅ ALWAYS do this
$activeUsers = User::where('is_active', true)->get();
$userEmailAddress = $user->email;
$totalOrderAmount = $order->calculateTotal();

// ❌ NEVER do this
$users = User::where('is_active', true)->get();
$email = $user->email;
$total = $order->calculateTotal();
```

### Collections & Arrays
```php
// ✅ Always use plural for collections
$users = User::all();
$activeOrders = Order::active()->get();
$validationRules = ['name' => 'required'];

// ✅ Always use singular for single items
$user = User::find(1);
$currentOrder = $user->orders()->latest()->first();
$validationRule = 'required|string|max:255';
```

## Method Complexity

### Single Responsibility
```php
// ✅ Each method should do one thing
public function createUser(array $userData): User
{
    $this->validateUserData($userData);
    $user = $this->buildUser($userData);
    $this->saveUser($user);
    $this->sendWelcomeEmail($user);
    
    return $user;
}

private function validateUserData(array $data): void
private function buildUser(array $data): User
private function saveUser(User $user): void
private function sendWelcomeEmail(User $user): void
```

## Modern PHP Features

### Use Readonly Properties (PHP 8.1+)
```php
class UserData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly array $roles,
    ) {}
}
```

### Use Enums (PHP 8.1+)
```php
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    
    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'Active User',
            self::INACTIVE => 'Inactive User',
            self::SUSPENDED => 'Suspended User',
        };
    }
}
```

### Use Match Expressions
```php
// ✅ Use match instead of switch for simple returns
public function getStatusColor(UserStatus $status): string
{
    return match($status) {
        UserStatus::ACTIVE => 'green',
        UserStatus::INACTIVE => 'gray',
        UserStatus::SUSPENDED => 'red',
    };
}
```

## IDE Support

### Always provide types for better IDE support
```php
// ✅ This enables full IDE autocomplete and error detection
/** @var Collection<int, User> $users */
$users = User::with('roles')->get();

/** @var User $user */
foreach ($users as $user) {
    // IDE knows $user is User instance
    echo $user->name;
}
```

## Quality Standards Priority

1. **Type Safety First** - Always add return types and parameter types
2. **Readability Second** - Use descriptive names and clear structure
3. **Performance Third** - Optimize only when necessary
4. **Maintainability Always** - Write code that's easy to modify and extend
5. **Never use deprecated methods** - never use deprecated methods in project classes

## Code Review Checklist

- [ ] All methods have return types
- [ ] All parameters have types
- [ ] All properties have types
- [ ] Variable names are descriptive
- [ ] Methods follow single responsibility principle
- [ ] PHPDoc is provided for complex methods
- [ ] Modern PHP features are used where appropriate
- [ ] No deprecated code or warnings

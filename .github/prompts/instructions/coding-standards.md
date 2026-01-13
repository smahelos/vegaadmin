# Code Quality Improvements & Priorities

> **This project uses Test-Driven Development (TDD). All new or changed code must be covered by Unit and Feature tests written before or together with implementation. Never merge code without tests.**

## Quality Standards Priority

1. **Type Safety First** – Always add return types and parameter types
2. **Readability Second** – Use descriptive names and clear structure
3. **Performance Third** – Optimize only when necessary
4. **Maintainability Always** – Write code that's easy to modify and extend
5. **Never use deprecated methods** – never use deprecated methods in project classes

## Code Quality Improvements (Order of Priority)

1. **Return Types (CRITICAL)**
    - All methods must have explicit return types
2. **Parameter Types (HIGH)**
    - All parameters must have type hints
3. **Property Types (HIGH)**
    - All properties must have type declarations
4. **Method Signatures (MEDIUM)**
    - All Laravel methods (controllers, requests, models) must have correct signatures
5. **Complex Type Documentation (MEDIUM)**
    - Use PHPDoc for complex arrays, return types, and data structures
6. **Modern PHP Features (LOW)**
    - Use readonly, enums, match, union/intersection types where appropriate

## IDE Support

- Always provide types for better IDE support and static analysis
- Use PHPDoc for complex types and collections
- Example:
```php
/** @var Collection<int, User> $users */
$users = User::with('roles')->get();
/** @var User $user */
foreach ($users as $user) {
    echo $user->name;
}
```

## Code Review Checklist

- [ ] All methods have return types
- [ ] All parameters have types
- [ ] All properties have types
- [ ] Variable names are descriptive
- [ ] Methods follow single responsibility principle
- [ ] PHPDoc is provided for complex methods
- [ ] Modern PHP features are used where appropriate
- [ ] No deprecated code or warnings

## Services – Example Signatures
```php
class UserService
{
    public function createUser(array $userData): User
    public function updateUser(User $user, array $data): User
    public function deleteUser(User $user): bool
    public function findUsersByRole(string $role): Collection
}
```

## Complex Type Documentation
```php
/**
 * @param array{name: string, email: string, roles: string[]} $userData
 * @return array{user: User, success: bool, message: string}
 */
public function processUser(array $userData): array
```

---

**Type safety and code quality are non-negotiable standards.**
---
mode: 'agent'
description: 'PHP 8.2+ and Laravel 12 coding standards and best practices'
---


# Coding Standards & Best Practices (Project-specific)
## Project-wide Rules
- All code and comments must be in English.
- All validation rules, error messages, and attribute names must use Laravel's translation system (see `lang/`).
- All code must follow conventions and structure described in `copilot-instructions.md` and other project documentation.
- All new or changed code must be covered by tests, following the project's test structure and naming conventions.
- Always use modern PHP and Laravel features (readonly, enums, match, attributes, etc.) where appropriate.

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

## PHPDoc Enforcement (NEW - 2025-08-17)
- All public methods in Services, Repositories, Value Objects, and Domain Contracts MUST include full PHPDoc with:
  - Summary line (what the method does)
  - @param tags for each parameter (ordered, with domain meaning)
  - @return tag with precise shape (use array shape & Collection generics where applicable)
  - @throws tags for domain-specific exceptions
- Private/protected methods handling complex logic MUST include at least summary + @return.
- Simple getters MAY omit PHPDoc only if return type is scalar and name is self-explanatory (exception: domain contracts still require it for documentation consistency).

### Examples
```php
/**
 * Get monthly aggregated statistics for charts (last N months).
 *
 * @param User $user Authenticated user whose data is aggregated
 * @param int $months Number of past months to include (default 6)
 * @return Collection<int, object> Collection of objects each with month + total
 */
public function getMonthlyStatistics(User $user, int $months = 6): Collection
```
```php
/**
 * Create a product with validated data.
 *
 * @param array{name:string,price:float,currency:string,category_id:int|null} $data Validated product payload
 * @return Product Newly created product entity
 * @throws ProductValidationException When domain validation fails
 */
public function create(array $data): Product
```

### Array Shape & Collection Tag Patterns
- Arrays: `@return array{invoice_count:int,client_count:int}`
- Collections: `@return Collection<int, Client>`
- Nested: `@return array{statistics:array{invoice_count:int},clients:Collection<int, Client>}`

> CI / Code Review MUST reject new public methods without compliant PHPDoc.

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

---
mode: 'agent'
description: 'Prompt for implementing strict coding standards and type safety'
---

# Apply Coding Standards & Type Safety

## 🎯 Primary Goals
1. **Type Safety First** - Add explicit return types and parameter types
2. **Modern PHP Features** - Use PHP 8.2+ features (readonly, enums, union types)
3. **Laravel Best Practices** - Follow Laravel 12 conventions
4. **IDE Support** - Provide complete type information for tooling
5. **Maintainability** - Write self-documenting, clear code

## Type Safety Analysis
Analyze the provided code and identify:
- [ ] Methods missing return types
- [ ] Parameters missing type hints
- [ ] Properties missing type declarations
- [ ] Variables that need better type documentation
- [ ] Complex arrays that need PHPDoc annotation

## Code Quality Improvements
Apply these improvements in order of priority:

### 1. Return Types (CRITICAL)
```php
// Before
public function getName()
public function getUsers()
public function findUser($id)

// After  
public function getName(): string
public function getUsers(): Collection
public function findUser(int $id): ?User
```

### 2. Parameter Types (HIGH)
```php
// Before
public function updateUser($id, $data)

// After
public function updateUser(int $id, array $data): User
```

### 3. Property Types (HIGH)
```php
// Before
protected $name;
protected $users;

// After
protected string $name;
protected Collection $users;
```

### 4. Method Signatures (MEDIUM)
```php
// Laravel Models
public function posts(): HasMany
public function user(): BelongsTo

// Laravel Requests
public function authorize(): bool
public function rules(): array
public function messages(): array

// Laravel Controllers
public function index(): View
public function store(Request $request): RedirectResponse
```

### 5. Complex Type Documentation (MEDIUM)
```php
/**
 * @param array{name: string, email: string, roles: string[]} $userData
 * @return array{user: User, success: bool, message: string}
 */
public function processUser(array $userData): array
```

### 6. Modern PHP Features (LOW)
- Use readonly properties where appropriate
- Use enums for constants
- Use match expressions instead of switch
- Use union types for flexible parameters

## Variable Naming Standards
Apply these naming conventions:

### Descriptive Names
```php
// Before
$data = User::all();
$result = $user->orders();
$items = $request->input();

// After
$allUsers = User::all();
$userOrders = $user->orders();
$formInputData = $request->input();
```

### Collections vs Singles
```php
// Collections (plural)
$activeUsers = User::active()->get();
$pendingOrders = Order::pending()->get();

// Single items (singular)
$currentUser = auth()->user();
$latestOrder = $user->orders()->latest()->first();
```

## Laravel-Specific Improvements

### Request Classes
```php
class UserRequest extends FormRequest
{
    public function authorize(): bool
    public function rules(): array  
    public function messages(): array
    public function attributes(): array
    protected function prepareForValidation(): void
}
```

### Model Relationships
```php
public function posts(): HasMany
public function comments(): HasManyThrough
public function user(): BelongsTo
public function tags(): BelongsToMany
```

### Service Classes
```php
class UserService
{
    public function createUser(array $userData): User
    public function updateUser(User $user, array $data): User
    public function deleteUser(User $user): bool
    public function findUsersByRole(string $role): Collection
}
```

## Code Review Process
After applying improvements:

1. **Verify Functionality** - Run tests to ensure nothing broke
2. **Check IDE Support** - Confirm autocomplete and error detection work
3. **Review Readability** - Ensure code is self-documenting
4. **Test Edge Cases** - Verify type safety catches errors
5. **Update Documentation** - Add PHPDoc where needed
6. **Replace deprecated code** - Ensure no deprecated methods or features are used

## Quality Standards Enforcement
- All new code MUST have explicit return types
- All parameters MUST have type hints  
- All properties MUST have type declarations
- Variable names MUST be descriptive and clear
- Methods MUST follow single responsibility principle
- No deprecated code or warnings allowed

## Report Issues Found
Document any patterns that need systematic improvement:
- Missing return types across multiple files
- Inconsistent naming conventions
- Opportunities for modern PHP features
- Areas needing better type documentation
- Complex methods that should be refactored

Remember: **Type safety and code quality are non-negotiable standards**.

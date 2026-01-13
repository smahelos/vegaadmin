---
mode: 'agent'
description: 'Prompt for refactoring existing code to improve quality'
---

# Refactor Code



# Refactor Code – Project Standards

> **This project uses Test-Driven Development (TDD). Before any refactoring, always write or extend tests to cover current behavior. Refactoring must never break existing tests.**

When refactoring existing code, always follow these project-wide rules:

## Refactoring Priorities (in order)
1. **Type Safety First** – Add explicit return types, parameter types, property types everywhere
2. **Readability Second** – Use descriptive names, clear structure, single responsibility
3. **Maintainability** – Extract methods, remove duplication, use traits/services
4. **Performance** – Optimize only when necessary
5. **Never use deprecated methods** – Remove all deprecated code and warnings

## Modern PHP & Laravel Standards
- Use PHP 8.2+ features: readonly, enums, match, union/intersection types
- All code and comments must be in English
- All validation/messages/attributes must use Laravel translations
- All new/changed code must be covered by tests
- Always use correct method signatures for Laravel (controllers, requests, models, services)
- Use PHPDoc for complex types/arrays
- Always provide types for IDE support

## Code Quality Improvements (apply in this order)
1. **Return Types** (CRITICAL)
2. **Parameter Types** (HIGH)
3. **Property Types** (HIGH)
4. **Method Signatures** (MEDIUM)
5. **Complex Type Documentation** (MEDIUM)
6. **Modern PHP Features** (LOW)

## Example Signatures
```php
// Controller
public function index(): View
public function store(UserRequest $request): RedirectResponse
// Service
public function createUser(array $data): User
public function deleteUser(User $user): bool
// Model
public function posts(): HasMany
// Request
public function authorize(): bool
public function rules(): array
```

## Code Review Checklist
- [ ] All methods have return types
- [ ] All parameters have types
- [ ] All properties have types
- [ ] Variable names are descriptive
- [ ] Methods follow single responsibility principle
- [ ] PHPDoc is provided for complex methods/arrays
- [ ] Modern PHP features are used where appropriate
- [ ] No deprecated code or warnings
- [ ] All code and comments are in English
- [ ] All validation/messages use translations

## IDE Support
- Always provide types for better IDE support and static analysis
- Use PHPDoc for complex types and collections


## TDD-First Refactoring Workflow

0. **Write/extend tests first (TDD)**
   - Ensure all current behavior is covered by Unit and Feature tests before refactoring
   - Add missing tests for uncovered logic or edge cases
   - Refactor only when all tests are present and passing


### 1. Analysis Phase
1. Read existing code thoroughly to understand current implementation
2. Identify code smells: long methods, duplication, tight coupling, missing types
3. Check test coverage and run all tests
4. Understand dependencies and relationships

### 2. Refactoring Actions
- Extract methods from long functions (single responsibility)
- Remove duplicated code (traits, services, helpers)
- Improve naming for clarity and intent
- Apply design patterns (Repository, Service, Strategy) where it improves maintainability
- Add type hints and return types everywhere
- Add/Improve error handling (specific exceptions, clear messages)
- Use modern PHP features (readonly, enums, match, union types)
- Use translation keys for all messages/validation
- Add/Update PHPDoc for complex types/arrays

### 3. Testing During Refactoring
- Run all existing tests frequently
- Add tests for new/refactored functionality
- Ensure no functionality is broken
- Improve test coverage if possible

### 4. Documentation
- Update comments and docblocks (in English)
- Add type hints for better IDE support
- Update README if architecture changes
- Document new patterns or conventions

---

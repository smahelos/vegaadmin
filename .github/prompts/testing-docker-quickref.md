---
mode: 'agent'
description: 'Quick reference for testing and docker commands'
---

# Quick Testing & Docker Reference

## 🚨 CRITICAL RULES - ALWAYS FOLLOW

### Docker Container
- **ALWAYS** use `docker exec vegaadmin-app php artisan [command]`
- **NEVER** run artisan commands directly on host
- **NEVER** use `-v` or `--verbose` with test commands

### Modern PHPUnit
- **ALWAYS** use `#[Test]` attribute (not `test` prefix)
- **ALWAYS** use `ReflectionClass` (not `ReflectionMethod`)
- **ALWAYS** test return types with reflection
- **ALWAYS** create unique test data with `uniqid()`

### Unit Test Isolation - CRITICAL
- **Unit tests MUST NOT depend on Laravel framework** (database, container, boot methods)
- **Avoid `new Model()` in Unit tests** - triggers boot methods requiring database
- **Move Laravel-dependent tests to Feature tests**: relationships, database ops, auth
- **Unit tests for pure business logic only**: calculations, transformations

### Request Test Structure
1. `request_extends_form_request()`
2. `authorize_uses_backpack_auth()` (test false/true scenarios)
3. `authorize_method_has_correct_return_type()`
4. `rules_returns_correct_validation_rules()`
5. `rules_method_has_correct_return_type()`
6. `attributes_returns_correct_custom_attributes()`
7. `attributes_method_has_correct_return_type()`
8. `messages_returns_correct_custom_messages()`
9. `messages_method_has_correct_return_type()`
10. `validation_passes_with_valid_data()`
11. `validation_fails_when_required_fields_missing()`
12. Additional business logic tests and edge cases

### Permission Setup
```php
// Always use firstOrCreate to avoid conflicts
Permission::firstOrCreate(['name' => 'permission_name', 'guard_name' => 'backpack']);

// Test authorization properly
$user = User::factory()->create();
$user->givePermissionTo('permission_name');
$this->actingAs($user, 'backpack');
```

### Required Return Types in Request Classes
```php
public function authorize(): bool
public function rules(): array
public function attributes(): array
public function messages(): array
```

## ✅ Correct Commands
```bash
docker exec vegaadmin-app php artisan test tests/Unit/Http/Requests/Admin/SomeTest.php
docker exec vegaadmin-app php artisan test tests/Unit/Http/Requests/Admin/
docker exec vegaadmin-app php artisan test --filter=RequestTest
```

## ❌ Wrong Commands (Will Fail)
```bash
# Missing docker container
php artisan test file.php

# Using verbose options
docker exec vegaadmin-app php artisan test file.php -v
docker exec vegaadmin-app php artisan test file.php --verbose
```

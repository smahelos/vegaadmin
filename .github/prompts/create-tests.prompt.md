---
mode: 'agent'
description: 'Prompt for creating comprehensive tests for any component'
---

# Create Tests for Component

When creating tests for any component, follow this comprehensive approach:

## Determine Test Type
1. **Models**: Split into Unit (structure, traits) and Feature (relationships, DB)
2. **Request Classes**: Split into Unit (rules, messages) and Feature (HTTP validation)
3. **Controllers**: Feature tests only (test HTTP workflows)
4. **Services**: Unit tests with mocked dependencies
5. **Repositories**: Feature tests with database interactions
6. **Middleware**: Feature tests with HTTP context

## Unit Test Template
```php
// Test class structure and methods without external dependencies
// Mock all external dependencies
// Test return values and method behaviors
// Fast execution, no database/HTTP
```

## Feature Test Template
```php
// Test real-world scenarios with full context
// Use RefreshDatabase trait
// Create explicit test data with factories
// Test complete workflows and integrations
```

## Required Test Patterns

### For Request Classes:
- **Unit**: Test rules(), authorize(), messages(), attributes()
- **Feature**: Test actual validation with HTTP context

### For Models:
- **Unit**: Test fillable, casts, accessors, mutators, traits
- **Feature**: Test relationships, scopes, database interactions

### For Controllers:
- **Feature**: Test all CRUD operations, permissions, responses

### For Services:
- **Unit**: Test business logic with mocked dependencies

## Test Data Setup
- Use faker for realistic test data
- Create helper methods for common setup
- Use explicit permissions and roles
- Avoid global seeders or hardcoded data

## Assertions to Include
- Test success and failure scenarios
- Test edge cases and boundary values
- Test permissions and authorization
- Test validation messages and error handling
- Test response formats and status codes

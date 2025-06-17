---
mode: 'agent'
description: 'Testing standards and best practices for Laravel application'
---

# Testing Instructions and Standards

## Test Organization
- **Unit Tests**: `tests/Unit/` - Test individual classes without dependencies
- **Feature Tests**: `tests/Feature/` - Test application features with full context
- **Integration Tests**: Test interactions between components

## Unit vs Feature Test Guidelines

### Unit Tests Should:
- Test model structure, accessors, mutators, traits (no database)
- Test request validation rules, authorization, messages (no HTTP context)
- Test service methods and business logic (mocked dependencies)
- Test helper functions and utilities
- Run fast without external dependencies

### Feature Tests Should:
- Test relationships and database interactions
- Test HTTP requests and responses
- Test full workflow scenarios
- Test with real database using RefreshDatabase
- Test authentication and authorization flows

## Test Standards
- Use `WithFaker` trait for generating test data
- Use explicit setup methods instead of global seeders
- Create helper methods for permissions, roles, and test data
- Test isolation - each test should be independent
- Use descriptive test method names
- Group related tests in logical test classes

## Request Class Testing Pattern
```php
// Unit Test: Test rules, authorize, messages methods
// Feature Test: Test actual validation scenarios with HTTP context
```

## Database Testing
- Use `RefreshDatabase` trait for database tests
- Create explicit test data with factories
- Test database constraints and relationships
- Clean up after tests automatically

## Permissions Testing
- Create permissions and roles explicitly in tests
- Use helper methods for user creation with roles
- Test authorization scenarios thoroughly
- Don't rely on existing seeded data

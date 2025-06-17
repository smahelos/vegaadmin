---
mode: 'agent'
description: 'Prompt for refactoring existing code to improve quality'
---

# Refactor Code

When refactoring existing code, follow this systematic approach:

## Analysis Phase
1. **Read existing code** thoroughly to understand current implementation
2. **Identify code smells**: Long methods, duplicated code, tight coupling
3. **Check test coverage** and run existing tests
4. **Understand dependencies** and relationships

## Refactoring Priorities
1. **Extract methods** from long functions
2. **Remove duplicated code** by creating reusable methods/traits
3. **Improve naming** for better readability
4. **Apply design patterns** (Repository, Service, Strategy)
5. **Add type hints** and return types
6. **Improve error handling**

## Specific Refactoring Patterns

### Controllers
- Extract business logic to Services
- Keep controllers thin (only HTTP concerns)
- Use Form Requests for validation
- Add proper error handling

### Models
- Extract common behaviors to Traits
- Add proper relationships
- Use accessors/mutators for data transformation
- Add scopes for common queries

### Services
- Make services stateless
- Use dependency injection
- Return consistent response formats
- Add proper error handling

### Requests
- Use translation keys for messages
- Add comprehensive validation rules
- Implement proper authorization logic

## Testing During Refactoring
- Run existing tests frequently
- Add tests for new functionality
- Ensure no functionality is broken
- Improve test coverage

## Documentation
- Update comments and docblocks
- Add type hints for better IDE support
- Update README if architecture changes
- Document new patterns or conventions

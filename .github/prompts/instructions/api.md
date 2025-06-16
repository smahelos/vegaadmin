---
mode: 'agent'
description: 'API development standards and conventions'
---

# API Development Instructions

## API Design Principles
- Follow RESTful conventions
- Use consistent response formats
- Implement proper error handling
- Include API versioning strategy

## Response Format Standards
```json
{
    "success": true,
    "data": {},
    "message": "Success message",
    "errors": []
}
```

## API Controllers
- Place in `app/Http/Controllers/Api/`
- Extend base API controller
- Use API resources for response formatting
- Implement proper status codes

## Authentication
- Use Laravel Sanctum for API authentication
- Implement token-based authentication
- Add rate limiting for API endpoints
- Use middleware for API protection

## Validation
- Use Form Requests for API validation
- Return validation errors in consistent format
- Include field-specific error messages
- Support multiple locales for error messages

## API Resources
- Use Laravel API Resources for data transformation
- Create resource collections for lists
- Hide sensitive data appropriately
- Include relationships when needed

## Error Handling
- Return appropriate HTTP status codes
- Use consistent error message format
- Log errors appropriately
- Include error codes for client handling

## Documentation
- Document all API endpoints
- Include request/response examples
- Specify required headers and parameters
- Document error responses

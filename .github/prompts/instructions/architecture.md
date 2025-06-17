---
mode: 'agent'
description: 'Project architecture and design patterns'
---

# Project Architecture and Design Patterns

## Clean Architecture Principles
- Keep project as clean and organized as possible
- Separate concerns between different layers
- Use dependency injection and service container
- Follow SOLID principles

## Form Request Organization
- **Frontend Requests**: `app/Http/Requests/` - For public-facing forms
- **Admin Requests**: `app/Http/Requests/Admin/` - For admin panel forms
- Include authorization, validation rules, attributes, and messages
- Use translation keys for all validation messages

## Service Layer Pattern
- Business logic in `app/Services/`
- Services should be stateless and focused
- Use dependency injection for service dependencies
- Return consistent response formats

## Repository Pattern
- Data access logic in `app/Repositories/`
- Abstract database operations from controllers
- Use Eloquent models within repositories
- Implement interfaces for better testability

## Trait Usage
- Common form field logic in `app/Traits/`
- Reusable model behaviors
- Shared validation rules
- Helper methods for controllers

## Helper Organization
- Static helper methods in `app/Helpers/`
- Grouped by functionality (DateHelper, UserHelpers, etc.)
- Pure functions without side effects
- Useful across multiple contexts

## Controller Responsibilities
- **Frontend Controllers**: Handle public user interactions
- **Admin Controllers**: Handle admin panel operations
- Keep controllers thin - delegate to services
- Handle only HTTP concerns (request/response)

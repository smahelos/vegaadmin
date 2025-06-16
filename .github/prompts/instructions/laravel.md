---
mode: 'agent'
description: 'Laravel 12 specific instructions and best practices'
---

# Laravel 12 Framework Instructions

## Framework Version
- Always use Laravel 12 features and syntax
- Follow Laravel 12 conventions and best practices
- Use Laravel 12 specific APIs and methods

## Code Standards
- All code, comments, and documentation should be in English
- Use descriptive variable and method names
- Follow PSR-4 autoloading standards
- Use type hints and return types where possible

## Laravel Features to Use
- Use Laravel 12 built-in translation system for multilingual support
- Utilize Laravel 12 validation system
- Use Eloquent ORM for database operations
- Leverage Laravel 12 form requests for validation
- Use Laravel 12 service container and dependency injection

## File Organization
- Controllers in `app/Http/Controllers/` (Frontend) and `app/Http/Controllers/Admin/` (Backend)
- Requests in `app/Http/Requests/` (Frontend) and `app/Http/Requests/Admin/` (Backend)
- Models in `app/Models/`
- Services in `app/Services/`
- Repositories in `app/Repositories/`
- Traits in `app/Traits/`
- Helpers in `app/Helpers/`

## Translation System
- Use `__('key')` for all translatable strings
- Store translations in `lang/{locale}/` directories
- Use dot notation for nested translation keys
- Support cs, de, en, sk locales

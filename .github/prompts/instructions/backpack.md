---
mode: 'agent'
description: 'Backpack 6.8 admin panel instructions and conventions'
---

# Backpack 6.8 Admin Panel Instructions

## Version
- Always use Backpack 6.8 features and syntax
- Follow Backpack 6.8 conventions and best practices
- Use Backpack 6.8 specific components and methods

## CRUD Controllers
- Extend `CrudController` for admin operations
- Use `setupListOperation()`, `setupCreateOperation()`, `setupUpdateOperation()`, etc.
- Place admin controllers in `app/Http/Controllers/Admin/`
- Use admin-specific requests in `app/Http/Requests/Admin/`

## Permissions and Authentication
- Use Laravel-Backpack/PermissionManager extension
- Base all authentication on spatie/laravel-permission
- Use roles and permissions for access control
- Check permissions in controllers and views

## Field Types and Operations
- Use Backpack field types for forms
- Utilize Backpack column types for lists
- Implement custom operations when needed
- Use Backpack's built-in validation

## Admin Routes
- Define admin routes in `routes/backpack/`
- Use Backpack's route protection middleware
- Follow Backpack URL conventions

## Views and Templates
- Extend Backpack base templates
- Use Backpack's blade components
- Follow Backpack's CSS/JS conventions
- Customize views in `resources/views/vendor/backpack/`

---
mode: 'agent'
description: 'Prompt for creating new CRUD operations with Backpack'
---

# Create CRUD Operation

When creating a new CRUD operation, follow this pattern:

## 1. Create Model
- Extend Eloquent Model
- Define fillable fields
- Add relationships
- Include necessary traits

## 2. Create Migration
- Use descriptive migration names
- Include proper indexes
- Add foreign key constraints
- Consider soft deletes if needed

## 3. Create Admin CRUD Controller
- Extend `CrudController`
- Implement setup operations (list, create, update, show, delete)
- Define columns and fields
- Add proper permission checks

## 4. Create Request Classes
- Admin request in `app/Http/Requests/Admin/`
- Frontend request in `app/Http/Requests/` (if needed)
- Include validation rules, authorization, messages
- Use translation keys for messages

## 5. Add Routes
- Admin routes in `routes/backpack/`
- Frontend routes in `routes/web.php` (if needed)
- Use proper middleware and permissions

## 6. Create Translations
- Add field labels in language files
- Include validation messages
- Support all locales (cs, de, en, sk)

## 7. Add to Navigation
- Update menu in Backpack config or view
- Check permissions for menu visibility

## Required Files Checklist:
- [ ] Model with relationships
- [ ] Migration with proper structure
- [ ] Admin CRUD Controller
- [ ] Request classes with validation
- [ ] Routes (admin + frontend if needed)
- [ ] Translations (all locales)
- [ ] Navigation menu entry
- [ ] Tests (Unit + Feature)

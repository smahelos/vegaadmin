Allways use newly read files, not what you remeber from past answers.

We use Laravel 12 as our main php framework, so when talking about Laravel, always give me instructions and code samples that use Laravel 12.

We use Backpack 6.8 to build custom admin panel, so when talking about Backpack, always give me instructions and code samples that use Backpack 6.8.

We use Tailwindcss 4.1.3 as main css framework, so when talking about Tailwindcss, always give me instructions and code samples that use Tailwindcss 4.1.3.

We use https://github.com/Laravel-Backpack/PermissionManager (Admin interface for spatie/laravel-permission. It allows admins to easily add/edit/remove users, roles and permissions, using Laravel Backpack.). Every user authentication methods sholud be based on this extension.

For best suggestions, give me instructions based on my codebase, existent classes files and existent methods.

For best suggestions, always read files to be changed, to not miss anything there.

We use Laravel 12 builtin translation system to make project multilingual, allways use translations for all translatable strings.

We want to have all comments in project in English, so all comments should be in English.

We want to have all code in project in English, so all code should be in English.

We want to have project as clean as possible. Formulars loads fields from App/Traits trait files. Frontend formulars requests are laoded from App/Requests folder. Backend formulars requests are laoded from App/Requests/Admin folder. Frontend authorization, validation rules, attributes and messages for formulars are placed in App/Requests folder. backend authorization, validation rules, attributes and messages for formulars are placed in App/Requests/Admin folder.

## Testing Guidelines

### Backpack Admin Tests Authentication
When creating tests for admin functionality that involve HTTP requests:

1. **Always use 'backpack' guard**: `$this->actingAs($user, 'backpack')`
2. **Create required permissions** in setUp() method using the permission list below
3. **Use withoutMiddleware()** to bypass Backpack middleware in tests 
4. **Use postJson/putJson** instead of post/put for proper JSON responses
5. **Expect 403** for unauthenticated admin requests (not 401)

### Required Permissions for Admin Tests
Create these permissions with 'backpack' guard in test setUp():
- can_create_edit_user, can_create_edit_invoice, can_create_edit_client
- can_create_edit_supplier, can_create_edit_expense, can_create_edit_tax  
- can_create_edit_bank, can_create_edit_payment_method, can_create_edit_product
- can_create_edit_command, can_create_edit_cron_task, can_create_edit_status
- can_configure_system, backpack.access

### Test Route Setup Example
```php
Route::post('/admin/resource', function (ResourceRequest $request) {
    return response()->json(['success' => true]);
})->middleware('web');
```

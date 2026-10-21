---
paths:
  - "app/Infrastructure/Authorization/**"
  - "app/Domain/User/**"
  - "app/Application/User/**"
  - "app/Application/Auth/**"
  - "app/Http/Middleware/**"
  - "app/Http/Requests/**/BaseEntityRequest.php"
  - "app/Http/Controllers/Admin/EntityLimitCrudController.php"
  - "app/Http/Controllers/Admin/UELSDashboardController.php"
  - "app/Http/Controllers/Api/UELSController.php"
  - "config/permission.php"
---

# Permissions and entity limits

## Guards and permissions

- Two guards: `backpack` for the admin, `web` for frontend users. A permission belongs to exactly one guard; frontend permissions are prefixed `frontend.`.
- Every permission the code checks is listed in `App\Infrastructure\Authorization\PermissionCatalog` (enforced by `PermissionCatalogCoverageTest`). A new permission goes into the catalog plus a new migration that runs `PermissionCatalogSync` again (pattern: `database/migrations/2026_10_18_000001_sync_permission_catalog.php`). The sync is additive: the `admin` role gets every backpack permission, frontend roles their defaults, `backend_user` nothing (PermissionManager only).
- Names: `can_{view,create,update,delete}_<entity>` for CRUD — one permission per Backpack operation (List/Show, Create, Update, Delete, gated with `CRUD::allowAccess()` / `denyAccess()`) and per policy ability. Other actions are `can_<verb>_<entity>` (`can_cancel_subscription`, `can_run_cron_task`); `can_configure_system` and `can_create_<entity>_unlimited` stay as they are. API endpoints check the same entity permission as the screen; `<guard>.api.access` only grants entry to the API.
- Removing or renaming a permission needs its own migration — the sync never deletes.
- Permission checks run in form requests (`authorize()`) and in application services; Blade only hides what the user cannot do, it never is the check.

## Entity limits (UELS)

- Creating a limited entity (invoice, client, supplier, product, …) is allowed only within the user's configured limit; **no configured limit means creation is not allowed**. The one exception is a guest invoice, which is never stored and is only offered for download.
- Limits are plan configuration: `limit_*` permissions with their `entity_limits` rows, assigned through the plan's role. They are not catalog entries.
- Admin users bypass limits through the catalog's `can_create_<entity>_unlimited` permissions.
- The limit check runs once, in the application service that creates the entity, inside the same transaction as the insert. Usage is recorded by a handler of the entity's `…Created` domain event, so every creation path — frontend, admin, API — counts.
- Time and periods come from `ClockInterface` and the requested period; never `now()` inside the limit services.

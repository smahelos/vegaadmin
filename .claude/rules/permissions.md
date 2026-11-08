---
paths:
  - "app/Infrastructure/Authorization/**"
  - "app/Domain/User/**"
  - "app/Application/User/**"
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
- Every permission the code checks is listed in `App\Infrastructure\Authorization\PermissionCatalog` (enforced by `PermissionCatalogCoverageTest`). A new permission goes into the catalog plus a new migration that runs `PermissionCatalogSync` again (pattern: `database/migrations/2026_10_25_000016_sync_permission_catalog.php`). The sync is additive: the `admin` role gets every backpack permission, frontend roles their defaults, `backend_user` nothing (PermissionManager only).
- Names: `can_{view,create,update,delete}_<entity>` for CRUD — one permission per Backpack operation (List/Show, Create, Update, Delete, gated with `CRUD::allowAccess()` / `denyAccess()`) and per policy ability. Other actions are `can_<verb>_<entity>` (`can_cancel_subscription`, `can_run_cron_task`); `can_configure_system` stays as it is. API endpoints check the same entity permission as the screen; `<guard>.api.access` only grants entry to the API.
- Removing or renaming a permission needs its own migration — the sync never deletes.
- Permission checks run in form requests (`authorize()`) and in application services; Blade only hides what the user cannot do, it never is the check.

## Entity limits

Design: `docs/adr/D-047-entity-limits.md`, behaviour: `docs/prd/PRD_ENTITY_LIMITS.md`.

- A frontend user holds exactly one plan role (`web` guard). Its row in `role_limits` sets the limits, one nullable integer column per limited entity: `NULL` unlimited, `0` not allowed, `N` the limit. A role without a row allows no limited creation. Limits are never merged from several roles and are not permissions.
- Two kinds of limit: **capacity** (businesses, contacts, products) caps the owner's active rows, so archiving or deleting frees a place and restoring takes one like creating; **monthly quota** (invoices, expenses) caps the rows created in the quota window, which starts at the later of the calendar month's start (application time zone) and the time the current plan role was assigned.
- Usage is counted from the entity's own rows: no usage table, counters or cache. Quota time is the system time of the action (creation, issuing once invoices are issued), never a date the user enters.
- The limit belongs to the record's owner, also when an admin creates it. No permission bypasses a limit. Admin reference data have no limits.
- The limit check runs once, in a domain service called by every use case that creates or restores a limited entity (registration's first business included), inside its transaction: it locks the owner's `users` row (`SELECT … FOR UPDATE`), reads the role's limit, counts the rows and lets the insert or restore run. Form requests, Livewire and Blade never check limits; Blade only shows usage rendered on the server.
- A guest invoice is never stored and has no limit. Creation endpoints and the guest invoice are rate limited, the same for all plans, with the values in `config/billex.php`.
- Time comes from `ClockInterface`; never `now()` inside the limit services.

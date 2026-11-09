---
paths:
  - "app/Infrastructure/Authorization/**"
  - "app/Domain/User/**"
  - "app/Application/**"
  - "app/Http/Controllers/**"
  - "app/Http/Requests/**"
  - "app/Http/Middleware/**"
  - "app/Livewire/**"
  - "config/permission.php"
---

# Permissions and entity limits

## Guards and permissions

- Two guards hold permissions: `backpack` for the admin, `web` for frontend users. The API runs on the session of one of them; the `api` guard holds none.
- Every permission is a case of `App\Domain\User\Enums\Permission`, whose value is the stored name (`Permission::CONTACT_CREATE` is `contact.create`). One case is one name, stored once per guard that uses it: no guard prefix and no `frontend:` / `admin:` pair. Code and templates never write a name as a string, only migrations do. `App\Infrastructure\Authorization\PermissionCatalog` assigns the cases to guards and roles, and `PermissionCatalogCoverageTest` keeps the enum and the catalog in step. A new permission gets a case and goes into the catalog plus a new migration that runs `PermissionCatalogSync` again (pattern: `database/migrations/2026_10_25_000016_sync_permission_catalog.php`). The sync is additive: the `admin` role gets every backpack permission, frontend roles their defaults, `backend_user` nothing (PermissionManager only).
- Names: `<entity>.{view,create,update,delete}` for CRUD — one permission per Backpack operation (List/Show, Create, Update, Delete). Other actions are `<entity>.<verb>` (`subscription.cancel`, `cron_task.run`, `business.set_default`). `system.configure` guards the system settings and `backpack.access` the entry to the admin. API endpoints check the same entity permission as the screen; `api.access` only grants entry to the API.
- Bank accounts have their own `bank_account.view` and `bank_account.update` (add, edit, archive, restore, default account), whichever party owns them.
- Removing or renaming a permission needs its own migration — the sync never deletes.

## Where permissions are checked

Why one gate: a form request protects only the HTTP endpoints that resolve it, API included, so Livewire, Artisan commands and other callers of the use case would bypass it; two checks hide which one protects the action; and a check written inside each use case can be forgotten unnoticed (`docs/adr/D-048-permission-checks.md`).

- **A use case runs only through the gate**: `$useCases->run($actor, new CreateContact($owner, $data))` on `AuthorizingUseCaseRunnerInterface`. In order, it checks the permission, checks the owner (with `permissionWithOwner` also the owner's permission), issues the `WriteScope` or `ReadScope`, opens the transaction (`application.md`) and calls the handler. A denied permission throws `App\Domain\User\Exceptions\PermissionDeniedException` (403); a missing or foreign record the context's not-found exception (404).
- **A use case declares its access once**, in the static `access(): Access` its interface requires: `Access::permission(Permission::CONTACT_VIEW)`, `Access::permissionWithOwner(Permission::CONTACT_CREATE)` (an admin writing for an owner, below), `Access::anyOf(…)` (one of several), `Access::allOwners(…)` (an admin reading every owner, gives `ReadScope::allOwners()`), `Access::public()` or `Access::system()`.
- **`Actor` is a closed set**, created only by its factory methods: `Actor::user($id)` (guard `web`), `Actor::admin($id)` (guard `backpack`), `Actor::automation($owner)` (with the owner's permissions, for bank connections), `Actor::system()`, `Actor::anonymous()`. Internally it holds an `ActorKind`. A factory method is added with its first caller. A user reaches only their own data, an admin anyone's, and `system` passes only a use case declared `system`. A call without a user takes the owner from stored data, never from input.
- **The owner and the scope.** A use case over one owner's data extends `OwnedUseCase` and gets a scope of that owner: `WriteScope` for a Command, `ReadScope` for a Query. A use case over every owner's data does not extend it: it declares `Access::allOwners(…)` or `Access::system()` and gets the read-only `ReadScope::allOwners()`. Any other use case gets no scope. The scope carries only the owner; a `WriteScope` also holds, in `$read`, the `ReadScope` of that owner, which the gate issues with it. Ports over an owner's data and over a user's own account require it (`persistence.md`). Scopes have a private constructor and a factory method that only the gate class calls, enforced by a rule in `phpstan/Rules/` with no exception for `tests/`.
- **Reading the permission.** Every run loads the user once and calls `checkPermissionTo($permission->value, $guard)` on that instance, with no cache between runs. A guard without that permission's row is denied; `hasPermissionTo` is not used, because it throws in that case.
- **A permission belongs to its effect.** A write to another entity runs that entity's Command as the record's owner, `$useCases->run(Actor::user($scope->owner), new CreateContact($scope->owner, $data))` (its own permission, limit, event, nested transaction); an acting admin needs only the main permission. That check runs after validation and its refusal rolls the whole action back. Reading a related record is covered by the main permission, through a port with the scope.
- **An admin writing for an owner needs the owner's permission too.** Create and update of contacts, businesses, products, invoices and expenses, and `subscription.create`, declare `Access::permissionWithOwner(…)`: the admin's permission in `backpack` and the owner's in `web`. The owner comes from the input, so the owner's check runs after validation. A user acting on their own data is checked once. A permission the owner cannot hold (no `web` row) uses `Access::permission`.
- **403 before validation.** Actions with a form request name their use case on the route (`allowed` middleware, `http.md`) and a Livewire action asks the same declaration on its first line. The owner check runs in the gate, after validation, so rules must reveal nothing about another user's records.
- **Display.** `@allowed(CreateContact::class)` reads the same declaration through the gate's `allowed` method and only hides what the user cannot do; it is never the check. `public` passes, `system` never does. It takes the user instance from the current default guard. A menu entry of a Backpack screen with no use case asks the `Permission` case for its guard (`checkPermissionTo`), like `setup()` does.
- **Backpack CRUD is denied by default**: `setup()` calls `CRUD::denyAllAccess()` and allows only the operations whose `Permission` the admin holds. Only reference data is persisted through models, with `setup()` as its only check. `setup()` only sets the operation flags; `hasAccessOrFail` enforces them, and an overridden `store`/`update` replaces the method that calls it. Anything with an owner therefore goes through a Command in an overridden `store`/`update` that starts with `$this->crud->hasAccessOrFail('create')` (or `'update'`) before `validateRequest()`, and the gate checks it again: the one intended double check, because Backpack needs `setup()` to hide operations.
- Every other admin action (dashboards, lookups, custom actions) runs a use case through the gate, like the frontend.

## Entity limits

Design: `docs/adr/D-047-entity-limits.md`, behaviour: `docs/prd/PRD_ENTITY_LIMITS.md`.

- A frontend user holds exactly one plan role (`web` guard). Its row in `role_limits` sets the limits, one nullable integer column per limited entity: `NULL` unlimited, `0` not allowed, `N` the limit. A role without a row allows no limited creation. Limits are never merged from several roles and are not permissions.
- Two kinds of limit: **capacity** (businesses, contacts, products) caps the owner's active rows, so archiving or deleting frees a place and restoring takes one like creating; **monthly quota** (invoices, expenses) caps the rows created in the quota window, which starts at the later of the calendar month's start (`app.schedule_timezone`) and the time the current plan role was assigned.
- Usage is counted from the entity's own rows: no usage table, counters or cache. Quota time is the system time of the action (creation, issuing once invoices are issued), never a date the user enters.
- The limit belongs to the record's owner, also when an admin creates it. No permission bypasses a limit. Admin reference data have no limits.
- The limit check runs once, in a domain service called by every use case that creates or restores a limited entity (registration's first business included), inside its transaction: it locks the owner's `users` row (`SELECT … FOR UPDATE`), reads the role's limit, counts the rows and lets the insert or restore run. Form requests, Livewire and Blade never check limits; Blade only shows usage rendered on the server.
- A guest invoice is never stored and has no limit. Creation endpoints and the guest invoice are rate limited, the same for all plans, with the values in `config/billex.php`.
- Time comes from `ClockInterface`; never `now()` inside the limit services.

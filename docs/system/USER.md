# User context

Status: maintained map of the code on `main`, last checked 2026-11-27. Start with [the system overview](OVERVIEW.md). The rules for permissions and limits are in `rules/permissions.md`; this page says where they live in the code.

Accounts, their roles and permissions, the entity limits of the plan roles, and the user's locale. Composition root: `UserServiceProvider`. Decisions: `docs/adr/D-047-entity-limits.md`, `docs/adr/D-048-permission-checks.md`, `docs/adr/D-049-grants.md`. Behaviour: `docs/prd/PRD_PERMISSIONS.md`, `docs/prd/PRD_ENTITY_LIMITS.md`, `docs/prd/PRD_GRANTS.md`.

## Entities

| Entity | Table | Code |
|---|---|---|
| User | `users` (soft deletes) | `App\Models\User`, `UserDTO`, `UserId`, ports `UserReadRepositoryInterface` and `UserWriteRepositoryInterface` (`EloquentUserRepository`) |
| Role, permission | Spatie tables (`roles`, `permissions`, `model_has_roles`, …), one set per guard | `Permission` enum (the names), `PermissionCatalog` (guards and default grants of the system roles) |
| Role limit | `role_limits`, one row per `web` role, one column per limited entity | `App\Models\RoleLimit`, `RoleLimitDTO`, port `RoleLimitReadRepositoryInterface` (`EloquentRoleLimitRepository`) |

## Use cases

Commands in `app/Application/User/Commands/`, queries in `app/Application/User/Queries/`, each next to its handler.

| Use case | Entry point | Access | What else happens |
|---|---|---|---|
| `Register` | `RegistrationForm` (Livewire) on `frontend.register` | public | the account gets the `frontend_user` role; its first business is created by Party's `CreateBusiness` as the new user, so the business limit applies |
| `GetProfile`, `UpdateProfile`, `ChangePassword` | `ProfileController`, routes `frontend.profile.*` | `profile.update` | — |
| `CreateUser`, `UpdateUser` | `Admin\UserCrudController` (`store`, `update`) | `user.create`, `user.update` | roles and direct permissions are written through `UserWriteRepositoryInterface`; `PlanRoleAssignments` records a changed plan role, which restarts the monthly quota |
| `AssignRole` | Artisan command `AssignUserRole` | system | the operator's role assignment, also through `PlanRoleAssignments` |
| `SearchUsers` | `Api\UserController`, route `api.admin.users.search` | all owners, `user.view` | the owner lookup of admin forms |
| `GetEntityUsage` | `EntityUsageController` (`admin.entity-usage.show`), the `EntityUsage` view component (`<x-entity-usage>` on the frontend create and list pages, the invoice issue dialog and the dashboard), the admin user detail | any create or update permission of a limited entity | counts only, never records |

Not through a use case:

- Login: `Frontend\Auth\LoginController` for the frontend, Backpack for the admin.
- Deleting a user: Backpack's delete operation in `UserCrudController` (soft delete); erasure is "Erasing a user account" in [TECH_DEBT](../TECH_DEBT.md).
- Roles and permissions: PermissionManager's screens, gated by `RoleCrudController` and `PermissionCrudController`.
- Role limits: `RoleLimitCrudController`, reference data stored by Backpack through the model.

## Permission checks

Every use case declares its access in its static `access()` (`Access`). The gate, `AuthorizingUseCaseRunner`, reads the actor's `Grants` from `GrantStore`, once per request, user and guard. A denied permission throws `PermissionDeniedException`, which `ExceptionHandling` turns into a 403. Adding a permission: the `add-permission` workflow (`.claude/skills/add-permission/SKILL.md`).

## Entity limits

| Piece | Code |
|---|---|
| Which entities are limited, capacity or monthly quota | `LimitedEntity` (the value is its `role_limits` column) |
| The check, inside the creating transaction | `EntityLimit::assertCanCreate()`, which locks the owner's `users` row |
| The limit of the owner's plan role | `RoleLimitReadRepositoryInterface`, read from `GrantStore` |
| The usage, counted from the entity's own rows | `LimitedEntityCounterInterface` (`EloquentLimitedEntityCounter`; invoices by `issued_at`, as Invoice's `IssueInvoiceHandler` checks the quota) |
| Refusal | `EntityLimitExceededException`; `ExceptionHandling` shows the entity's `<file>.messages.limit_exceeded` translation, Livewire forms catch it themselves |
| Usage for display | `EntityLimit::usage()` through `GetEntityUsage` |
| The limits per role, edited in the admin | `RoleLimitCrudController`, `RoleLimitFormFields`, `RoleLimitRequest` |

Handlers that call `assertCanCreate()`: `CreateInvoiceHandler`, `CreateExpenseHandler`, `CreateBusinessHandler`, `RestoreBusinessHandler`, `CreateContactHandler`, `RestoreContactHandler`, `CreateProductHandler`. Product archiving does not exist yet ("Products cannot be archived" in [TECH_DEBT](../TECH_DEBT.md)).

Code that names each limited entity, and so changes with a new one:

- the `LimitedEntity` enum and the `role_limits` columns (a migration), the `App\Models\RoleLimit` fillable and casts, `EloquentRoleLimitMapper`, `RoleLimitDTO::limit()`;
- the plan role limits seeded by `ReferenceDataSeeder` (`PLAN_ROLE_LIMITS`); a new column is `NULL`, unlimited, until they are set;
- the two `match` of `EloquentLimitedEntityCounter` (the entity's rows);
- `ExceptionHandling::limitMessage()` with the entity's `<file>.messages.limit_exceeded` key, and `GetEntityUsage::access()`;
- the entity's screens: `<x-entity-usage>` on its frontend create and list pages, `EntityUsageFormFields` on its admin form.

The admin role limit form, its request and list, the admin user detail and the usage display loop over `LimitedEntity::cases()`; they need only the `role_limits.fields.<value>` and `role_limits.usage.<value>` translations. The list of limited entities is also written in `docs/prd/PRD_ENTITY_LIMITS.md`, `docs/adr/D-047-entity-limits.md` and `rules/permissions.md` (Entity limits).

Rate limits of creation endpoints (`throttle:create`) are not entity limits; their values are in `config/billex.php`.

## Events

`UserDataChanged` is published by `InvoiceObserver` (Invoice context), not by this context; `InvalidateUserCache` clears the user's dashboard cache. The User use cases publish no events.

## Locale

The `Locale` port (`LaravelLocaleAdapter`) gives the current, available and fallback UI languages and chooses one from the request; `LocaleServiceInterface` (`LocaleService`) maps a country to its language.

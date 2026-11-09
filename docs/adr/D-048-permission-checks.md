# D-048: Permissions and Ownership Are Checked by One Gate

Status: Accepted 2026-11-09
Related: `.claude/rules/permissions.md` (Where permissions are checked), `.claude/rules/application.md`, `.claude/rules/http.md`, [D-047](D-047-entity-limits.md) (entity limits, checked in the handler, inside the transaction).
Decided with the project owner on 2026-11-09, after the review of TECH_DEBT step 9. The conversion is tracked in `docs/TECH_DEBT.md`.

## Context

Permissions were checked in several places: `authorize()` of form requests, `requirePermission()` in Livewire components, `authorize…()` methods a controller called before the action, `setup()` of Backpack controllers, a policy, and some use cases.

- A form request exists only for HTTP, so Livewire, API and console entry points bypass its check.
- Two checks of one action hide which of them protects it. Removing either looks safe, and the overridden `store`/`update` of businesses, contacts and bank accounts were in fact protected only by `authorize()`.
- A controller calling `authorize…()` and then the action can forget the first call.
- A check written as the first line of every application service method can be forgotten the same way, because PHP cannot wrap a method in a check.

## Decision

1. **The gate checks, once per action**: first the permission, then the owner. A denied permission is `PermissionDeniedException` (403), a missing or foreign record the context's not-found exception (404). A user without the permission gets a 403 whether the record exists or not, so existence stays hidden.
2. **A form request only validates.** It has no `authorize()`.
3. **A use case runs only through the gate, with an `Actor`.** `$useCases->run($actor, new CreateContact($owner, $data))` checks the permission and the owner, issues the `WriteScope` or `ReadScope`, opens the transaction and calls the handler. The actor is a closed set: user, admin, automation on behalf of an owner, system, anonymous. A user reaches only their own data, an admin anyone's, and `system` passes only a use case declared `system`. A call without a user takes the owner from stored data, never from input.
4. **A use case declares its access once.** The interface requires a static `access(): Access`: `permission`, `anyOf`, `allOwners`, `public` or `system`. One `Permission` case is one name, stored once per guard that uses it. The gate reads it on every run for the actor's guard (`checkPermissionTo`); a guard without that row is denied. `@allowed` reads the same declaration and only hides what the user cannot do.
5. **`Permission` is an enum whose value is the stored name.** One case is one name, with a row for each guard that uses it. Code and templates never write a name as a string.
6. **Backpack CRUD is denied by default** in `setup()`, and only operations whose permission the admin holds are allowed. Only reference data is persisted through models, with this as its only check.
7. **An overridden `store`/`update` is checked twice on purpose**: `setup()` gates the operation and the gate checks the write. Backpack needs `setup()` to hide operations, and the use case must not need a bypass for admins.
8. **Every other admin action runs a use case through the gate**, also the system screens.

## Alternatives

- **Keep the check in form requests:** bypassed by every non-HTTP entry point.
- **A permission check inside every application service method:** forgetting it is invisible, and nothing issues proof that the owner was checked.
- **One class per use case, with its dependencies injected into `handle`:** fewer files, but an exception to constructor injection (`php.md`).
- **Pass when the user holds either guard's permission:** an account with the frontend permission and access to the admin would pass an admin operation it has no right to.
- **Delivery passes the permission to the use case:** returns the decision to the layer it was taken from.
- **A different stored name per guard, composed or paired in code:** needs a prefix and either names built at runtime or a `frontend:` / `admin:` pair at every use case.
- **Separate use cases for admin and frontend:** duplicates every shared use case.
- **A use case that does not check admins, with `setup()` as the only admin check:** needs a bypass in the use case and leaves other admin entry points unprotected.
- **A first-line check in admin actions without a use case, or keeping `authorize()` there:** an exception to the one rule.

## Consequences

- A missing permission is answered with a 403 before validation, from the use case's declaration. The owner check runs in the gate, after validation, so rules must reveal nothing about another user's records.
- `authorize()` is removed only in the commit that adds the check replacing it.
- A broad `catch` around a use case would turn a 403 or 404 into a generic error, so such catches are narrowed with the conversion.
- A Command that calls outside the database declares it with `CallsOutsideDatabaseInterface`, and its handler opens the transaction only around its writes.
- The system admin screens need use cases and ports before their requests lose `authorize()`. They form the `System` context. `DatabaseDashboardController` follows in TECH_DEBT step 20 and keeps its form request until then.

# D-049: What an Actor May Is Loaded Once per Request

Status: Accepted 2026-11-16
Product specification: [PRD: What an actor may, read once per request](../prd/PRD_GRANTS.md).
Related: `.claude/rules/permissions.md` (Reading the permission, Display, Entity limits), amends [D-048](D-048-permission-checks.md) point 4 and [D-047](D-047-entity-limits.md) point 8.
Decided with the project owner on 2026-11-16, after phase 6b of TECH_DEBT step 11.

## Context

Four places answered what a signed-in person may do, each reading the roles on its own:

- the gate's `run()` loaded the user with `User::find` and called `checkPermissionTo` on every run, with nothing kept between runs (D-048 point 4);
- the gate's `allowed()` and the API entry middlewares used the user instance of the guard;
- the admin menu (41 checks), Backpack `setup()`, the admin form requests and the cron run button called `checkPermissionTo` or `can()` on `backpack_user()`;
- `EntityLimit` read the plan role's row from `role_limits` on every check (D-047 point 8).

One request asks several of them. A dashboard chart read the roles three times (the `allowed` middleware, the API entry middleware and `run()`), the five charts of the dashboard fifteen times. Keeping the guard's instance for `allowed` and resetting it after each Command was rejected: it keeps two sources of the user and works around the rule that the gate reads the permission.

## Decision

1. **Grants.** `App\Infrastructure\Authorization\Grants` is what one user may on one guard: the names of the permissions held directly or through a role, only on that guard, and the `role_limits` rows of that guard's roles. It holds exactly what `checkPermissionTo($name, $guard)` accepts: wildcard permissions and teams are off and nothing is registered in `Gate::before`.
2. **One store per request.** `App\Infrastructure\Authorization\Services\GrantStore` loads the grants of a user and guard on the first question and keeps them for the rest of the request (`for(UserId, string $guard)`). It is registered as `scoped`, and `SharedServiceProvider` empties it whenever a new request is bound (`rebinding('request')`): the HTTP kernel binds every request anew, also each request and Livewire call of a test, which run in one process. It is emptied rather than replaced, because a controller kept on its route from an earlier request still holds the instance. The queue worker forgets scoped instances between jobs itself.
3. **Only the gate asks.** `run()`, `allowed()` and the new `holds(Actor, Permission)` of `AuthorizingUseCaseRunnerInterface` read the same grants; an admin writing for an owner reads the owner's `web` grants apart from their own. `holds` and Blade's `@holds(Permission::X, …)` (any of the cases) serve the places with no use case: the admin menu, Backpack `setup()`, the admin and API entry middlewares and the cron run button. `@holds` asks for the admin of the `backpack` guard by name: Backpack renders an admin error page with the menu before any middleware has switched the default guard. Where a use case exists, delivery keeps asking `allowed`. `allowed` answers for the actor it is given, no longer only for the signed-in user of its guard.
4. **Limits from the grants.** `EloquentRoleLimitRepository::listForUser()` returns the limit rows of the user's `web` grants; the port `RoleLimitReadRepositoryInterface` and `EntityLimit` are unchanged.
5. **One writer forgets.** `EloquentUserRepository` is the only code in `app/` that changes a user's roles or direct permissions (`assignRole`, `syncRolesAndPermissions`), and it forgets that user's grants after the write, so the rest of the request works under the new role: registration creates the first business under the role assigned a moment before, and `UpdateUser` reads the plan role again after the change. The write forgets before the commit, so a rolled back transaction empties the store (`TransactionRolledBack`, `SharedServiceProvider`) and the rest of the request does not keep roles that were never stored. `PermissionCatalogSync` is the only code that links roles to permissions. The Larastan rule `RolesWrittenByRepositoryRule` reports any other call of Spatie's write methods or of the `roles()` / `permissions()` relations in `app/`; tests, seeders and migrations set roles directly.
6. **Entry to the admin is `backpack.access`.** The middleware no longer requires some role on the `backpack` guard as a separate condition; `backpack.access` held directly admits too (PRD_GRANTS A09).

## Alternatives

- **The actor carries its grants:** every place that creates an `Actor` (47) would load them, and an immutable actor keeps them stale after a role change in the same request.
- **One query per check, nothing kept:** `@allowed` and the admin menu would query the roles dozens of times per page.
- **The guard's instance for `allowed`, a reset after each Command:** two sources of the user and a workaround around the gate.
- **A memory without reset per request:** a later request or a test with several requests would work with roles read before it.
- **Forgetting every scoped instance on a new request:** other scoped services keep the lifetime the framework gives them.
- **`backpack.access` plus an admin role in the grants:** a second condition next to the permission, which the admin user form can grant directly.

## Consequences

- A request reads a user's roles, direct permissions and role limits once per guard, however many checks, menu entries, buttons and limit checks it holds (`GrantStoreTest`).
- A change of a role's permissions in the admin role screen, or of a role's limits in the role limits screen, applies from the next request; a request already running finishes with what it read.
- The limit is no longer read under the owner's lock. The usage is still counted under it, so two simultaneous creations at the last free place give one record; a creation running together with a plan change in another request counts against the plan its own request read.
- The 17 admin form requests that check permissions in `authorize()` keep their own reading until phase 8 of step 11, and the frontend navigation's check of the `frontend_user_plus` role stays until the plan roles are reworked. A Larastan rule against reading permissions outside the gate comes with phase 8.

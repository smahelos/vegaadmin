---
name: add-permission
description: >-
  Add, rename or remove a Billex permission or role: PermissionCatalog, the sync migration, role
  grants and the coverage test. Use whenever code starts or stops checking a permission
  (use case, Backpack operation access) or a role's
  default permissions change.
---

# Add, rename or remove a permission

Naming and where checks belong: `rules/permissions.md`. Entity limits (`role_limits`, D-047) are plan configuration, not permissions — this skill does not handle them.

1. **Pick the name.** `<entity>.{view,create,update,delete}` for CRUD, `<entity>.<verb>` for other actions; the name has no guard prefix. Look in `app/Infrastructure/Authorization/PermissionCatalog.php` first: if the entity already has the permission for this action, use it and stop here.
2. **Edit the enum and the catalog.** Add the case to `App\Domain\User\Enums\Permission` (`CONTACT_CREATE`, its value is the name, `contact.create`) and the permission to `PermissionCatalog::PERMISSIONS[<guard>]` of every guard that uses it, keeping the list's order; add it to the default permissions of the frontend roles in `PermissionCatalog::ROLES` if they should have it. The `admin` role receives every backpack permission automatically.
3. **Write the migration.** `docker exec BILLEX-app php artisan make:migration <verb>_<permission>_permission`:
   - adding → `up()` calls `app(PermissionCatalogSync::class)->sync()`, `down()` stays empty (the permission may already be granted), as in `database/migrations/2026_10_25_000016_sync_permission_catalog.php`;
   - renaming or removing → `up()` only renames or deletes the permission explicitly and then calls `forgetCachedPermissions()`, without the sync: a pure rename creates nothing and the sync would grant back every catalog default a role had revoked (renaming keeps existing grants; for removal, check with the user who holds it now); `down()` restores the old name. Splitting one permission into several is the adding path followed by the removal, as in `database/migrations/2026_11_08_000001_finish_splitting_permissions_by_operation.php`.
4. **Use it.** In the `access()` of the Command or Query, or in the Backpack `setup()` — whichever owns the check (`rules/permissions.md`). Remove every use of a removed permission together with its enum case (`grep -rn "Permission::<CASE>" app routes resources tests`).
5. **Verify.**
   - `docker exec BILLEX-app php artisan test tests/Feature/Architecture/PermissionCatalogCoverageTest.php` — the enum and the catalog list the same permissions.
   - the migration on the local database — ask the user before running `docker exec BILLEX-app php artisan migrate`; then the HTTP tests of the routes the permission guards: a user without it gets 403, a user with it passes.

Done when the enum, catalog, migration, checks and tests all name the same permission and the tests above pass.

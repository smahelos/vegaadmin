---
name: add-permission
description: >-
  Add, rename or remove a Billex permission or role: PermissionCatalog, the sync migration, role
  grants and the coverage test. Use whenever code starts or stops checking a permission
  (form request authorize(), Backpack operation access, policy, application service) or a role's
  default permissions change.
---

# Add, rename or remove a permission

Naming and where checks belong: `rules/permissions.md`. Entity limits (`limit_*`) are plan configuration, not catalog entries — this skill does not handle them.

1. **Pick the name.** `can_{view,create,update,delete}_<entity>` for CRUD, `can_<verb>_<entity>` for other actions; web-guard permissions get the `frontend.` prefix. Look in `app/Infrastructure/Authorization/PermissionCatalog.php` first: if the entity already has the permission for this action, use it and stop here.
2. **Edit the catalog.** Add the name to `PermissionCatalog::PERMISSIONS[<guard>]`, keeping the list's order; add it to the default permissions of the frontend roles in `PermissionCatalog::ROLES` if they should have it. The `admin` role receives every backpack permission automatically.
3. **Write the migration.** `docker exec BILLEX-app php artisan make:migration <verb>_<permission>_permission`:
   - adding → `up()` calls `app(PermissionCatalogSync::class)->sync()`, `down()` stays empty (the permission may already be granted), as in `database/migrations/2026_10_25_000016_sync_permission_catalog.php`;
   - renaming or removing → the sync never deletes, so `up()` renames or deletes the permission explicitly (renaming keeps existing grants; for removal, check with the user who holds it now) and then runs the sync; `down()` restores the old name.
4. **Use it.** In the form request's `authorize()`, the Backpack operation setup, the policy or the application service — whichever owns the check (`rules/permissions.md`). Remove every check of a removed name (`grep -rn "'<old name>'" app routes resources tests`).
5. **Verify.**
   - `docker exec BILLEX-app php artisan test tests/Feature/Architecture/PermissionCatalogCoverageTest.php` — every permission the code checks is in the catalog.
   - the migration on the local database — ask the user before running `docker exec BILLEX-app php artisan migrate`; then the HTTP tests of the routes the permission guards: a user without it gets 403, a user with it passes.

Done when the catalog, migration, checks and tests all name the same permission and the tests above pass.

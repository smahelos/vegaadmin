# PRD: What an actor may, read once per request

Date: 2026-11-16. Version: 1.0. Status: approved product specification (project owner, 2026-11-16).
Decision: [D-049](../adr/D-049-grants.md). Related: [PRD: Permissions](PRD_PERMISSIONS.md) (4.5, 4.6), [PRD: Entity limits](PRD_ENTITY_LIMITS.md) (4.3, 4.5), [D-048](../adr/D-048-permission-checks.md), [D-047](../adr/D-047-entity-limits.md). The code standard is in `.claude/rules/permissions.md`, the work is tracked in `docs/TECH_DEBT.md` (step 11).

This document states what users and admins can observe. How the permissions and limits are read and kept is decided in D-049.

## 1. Problem and goal

Four places answer what a signed-in person may do, each reading the roles on its own: the gate before an action, the gate when a screen hides what the person cannot do, the admin menu and CRUD screens, and the entity limits. One request asks several of them, so the same roles are read repeatedly: one dashboard chart reads them three times, the five charts of the dashboard fifteen times, and the admin menu up to forty times.

The goal is that:

- one request reads what a person may (permissions and plan limits) once, however many checks and screen elements ask;
- every place gives the same answer, because it asks the same source;
- a change of a person's role made by the application applies at once, and no request works with roles older than its own start.

## 2. Terms

- **Side**: the frontend (`web` guard) or the admin (`backpack` guard), as in [PRD: Permissions](PRD_PERMISSIONS.md).
- **Grants**: what one person may on one side: their permissions and, on the frontend side, the limits of their plan role.
- **Request**: one HTTP request, one queued job or one console command.

## 3. Required behaviour

### 3.1 One answer

- Who may do what does not change: every role and user keeps exactly the abilities and limits they had.
- The admin menu, the availability of admin CRUD operations, the entry to the admin and to the API, the cron task run button and the hiding of frontend buttons and links give the same answer as the check of the action they lead to ([PRD: Permissions](PRD_PERMISSIONS.md), 4.5).
- Entry to the admin requires `backpack.access` on the admin side. Holding some admin role is no longer a separate condition, so a person given `backpack.access` directly, without an admin role, enters the admin.

### 3.2 Reading once

- A request reads the grants of one person on one side at most once, whatever number of checks, menu entries, buttons and limit checks it contains.
- An admin acting for an owner reads the owner's frontend grants once as well, separately from their own.
- Each request starts without any grants read by an earlier request, also when several requests or jobs run in one process.

### 3.3 When a change applies

- A role assigned or replaced through the application (registration, the admin user form, `user:assign-role`, and the plan change of step 19) applies to the rest of the same request and to every later one. Registration relies on this: the new account's first business is created under the role assigned a moment before.
- A change of a role's permissions in the admin role screen, and a change of a role's limits in the role limits screen, applies from the next request of each affected person, without signing in again. A request already running finishes with what it read.
- This keeps [PRD: Permissions](PRD_PERMISSIONS.md) 4.6 (a change applies from the person's next action) and [PRD: Entity limits](PRD_ENTITY_LIMITS.md) 4.3 (a new plan role applies immediately).

### 3.4 Limits

- The limit check uses the limits of the request's grants. Usage is still counted at the moment of creation under the owner's lock, so two simultaneous creations at the last free place still give exactly one record.
- A creation running at the same time as a plan change in another request counts against the plan its own request read. A plan change does not wait for creations in progress, as today.

## 4. Exclusions

- The 17 admin form requests that still check permissions in `authorize()` keep their own reading until phase 8 of step 11.
- The frontend navigation's check of the plan role (`frontend_user_plus`) stays until the plan roles are reworked.
- The admin role screen's permission editor is a package screen: its changes apply from the next request (3.3) and are not tracked separately.
- No change of roles, permissions, limits or their administration screens.

## 5. Acceptance criteria

| ID | Criterion |
|---|---|
| A01 | Every role and user holds the same abilities and limits as before the change. |
| A02 | A request with several checks, hidden elements and a limit check reads the roles, the permissions and the role limits of one person on one side at most once each. |
| A03 | One dashboard chart request reads them once, not three times. |
| A04 | An admin writing for an owner reads the owner's grants once, apart from the admin's own. |
| A05 | A role assigned through the application is in force for the next action of the same request; a new account's first business is created under the role assigned at registration. |
| A06 | A role or permission changed between two requests is in force in the second one, also between two Livewire requests, without signing in again. |
| A07 | A change of a role's limits applies from the user's next request. |
| A08 | The admin menu, admin CRUD operations, admin and API entry and the cron run button are shown and allowed exactly when the actor holds the permission, the same as the check of the action. |
| A09 | A person with `backpack.access` on the admin side enters the admin without holding an admin role; a person without it is refused. |
| A10 | A frontend permission does not show or allow an admin element, and the other way round. |

## 6. Delivery

One PR after phase 6b of step 11 (branch `ls-grants`), covering sections 3.1–3.4 and A01–A10. The admin form requests follow in phase 8.

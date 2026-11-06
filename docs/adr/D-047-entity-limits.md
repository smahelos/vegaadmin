# D-047: Entity Limits Are Plan Role Rows Counted From the Entities

Status: Proposed 2026-11-06
Product specification: [PRD: Entity limits](../prd/PRD_ENTITY_LIMITS.md).
Related: [D-045](D-045-businesses-and-contacts.md) (capacity of businesses and contacts, downgrade), `.claude/rules/permissions.md` (Entity limits).
Decided in an interview with the project owner on 2026-11-06 (TECH_DEBT step 8). The original UELS documents (`docs/UELS_*.md` in commit `a65aaffb` of the Bitbucket history) were not used as a requirement; this ADR replaces their design.

## Context

A frontend user's plan decides how many records of each kind they may create. Before this decision the limits ran through three layers: a role held `limit_*` permissions, each permission had rows in `entity_limits` (entity type, period, metric, value), and `entity_limit_usage` counted creations per period, written by handlers of the `…Created` domain events.

The analysis of 2026-11-06 (TECH_DEBT step 8) found:

- Of several period limits of one entity only one applied: the service picked the period with the largest monthly capacity, so the seeded 3 invoices a day, 15 a week and 10 a month allowed about 90 a month.
- When a user held several roles, the highest limit won, so a forgotten role silently kept a higher limit.
- The metric types `value` and `size`, `default_period`, the entity types `user` and `page` and the anonymous limits were never used; `current_value` is a count stored as `decimal`.
- The checks were inconsistent: frontend invoices were checked twice, product limits were not enforced, the expense bypass read the owner's permissions instead of the admin's, and admin reference data (taxes, banks, payment methods, product categories) could only be created through `can_create_<entity>_unlimited`.
- A failed usage recording was only logged, so the counter could drift from the records it counts.
- Subscription plans had no link to roles; `subscription_plan_features` is free text that can contradict the enforced limits.

## Decision

1. **Two kinds of limit only.**
   - **Capacity** caps the owner's active rows: businesses, contacts and products. Archiving or deleting frees a place, restoring takes one ([D-045](D-045-businesses-and-contacts.md) point 12).
   - **Monthly quota** caps the rows created in the quota window: invoices and expenses.
   - No daily, weekly or yearly limits and no several periods per entity. Abuse protection is a rate limit (point 9), not a plan limit.
2. **The quota window** starts at the later of the start of the current calendar month (application time zone) and the time the user's current plan role was assigned, and ends now. A user who changes plan pays the full price and gets the full quota of the new plan; from the next month the window is the calendar month again. The assignment time is stored with the user and set at every plan role change.
3. **Quota time is the system time of the action**: the creation of the record, and for invoices the time of issue once step 12 introduces issuing. A date the user enters on the document never counts.
4. **Usage is counted from the entities' own rows.** There is no usage table, no counter, no reset and no cache. Deleting a record returns its place in the quota; this is accepted: issued invoices cannot be deleted after step 12, and deleting an expense to free a place costs the user their data.
5. **Limits are rows of `role_limits`**, one row per `web` guard role, with one nullable integer column per limited entity (`invoices`, `expenses`, `businesses`, `contacts`, `products`): `NULL` means unlimited, `0` not allowed, `N` the limit. A role without a row allows no limited creation. The row references `roles` by a foreign key with `ON DELETE CASCADE`; Spatie's tables and PermissionManager are not changed. Admins edit the rows in Billex's own Backpack CRUD screen, gated by `can_{view,create,update,delete}_role_limit`.
6. **A frontend user holds exactly one plan role.** Limits are never merged from several roles and there are no per-user overrides; an exception is handled by changing the role. Other roles a user may hold carry no limits.
7. **The limit belongs to the record's owner**, also when an admin creates the record for them. No permission bypasses a limit: `can_create_<entity>_unlimited` is removed. Admin reference data have no limits; their `can_create_*` permission is the only check.
8. **One check.** A domain service checks both kinds of limit, called by every use case that creates or restores a limited entity (including registration's first business), inside its transaction: it locks the owner's `users` row (`SELECT … FOR UPDATE`), reads the role's limit, counts the rows and lets the insert or restore run. Form requests, Livewire and Blade do not check limits; Blade only shows usage, rendered on the server.
9. **Rate limits** on creation endpoints are the same for all users, per user, and per IP for the guest invoice, which is never stored and has no plan limit. Their values live in `config/billex.php`, the file for Billex settings that do not change at runtime.
10. **Plans point to roles** (`subscription_plans.role_id`, step 19). Activating a subscription assigns the plan's role; the end of the subscription returns the user to `frontend_user`. Several plans (monthly, yearly) may share a role. The limit lines a plan shows are generated from `role_limits`; the manual plan features hold only text the role cannot express and never a limit value.
11. **Removed:** `entity_limits`, `entity_limit_usage`, the `limit_*` permissions, the usage recording handlers, `UniversalLimitService`, `PermissionLimitResolver`, the UELS cache and its permission change listener, the UELS API, `uels-manager.js` and the `uels-*` components, the `EntityLimit` and `EntityUsage` CRUD screens and the UELS dashboard. `CapacityLimit` is replaced by the service of point 8.

## Alternatives Considered

| Option | Reason rejected |
|---|---|
| Keep `limit_*` permissions with `entity_limits` rows | Three places for one number, and the merge of several roles makes the effective limit hard to explain. |
| Limits on `subscription_plans` | The free tier has no plan; it would need a fake plan record, and roles already exist for every user. |
| Narrow `role_limits` (one row per role and entity) | A plan is spread over several rows, "not allowed" has two spellings (missing row, `0`), and a per-role form needs custom saving. A new limited entity needs code anyway, so a column costs little. |
| Limit fields inside PermissionManager's role form | Overriding a package controller couples Billex to every package upgrade. |
| Limits in code without an admin screen | Every change of a number would need a deploy. Kept as a fallback if admins never change limits. |
| Several periods per entity, all applying | More rows, more checks and a harder message for the user, without a plan that needs it. |
| A monthly limit with a derived daily limit | Penalises legitimate bursts such as invoicing at the end of the month. |
| A quota window following the subscription's billing cycle | Free users have no cycle, the dashboard would show "until 19 Dec" instead of "this month", and month ends (a subscription started on the 31st) need special handling. |
| A usage counter table (the original design) | Two sources of truth that can drift, with repair and reset tooling. Its only advantage, that deletion does not refund, is not needed (point 4). |
| Several roles with the highest limit, or per-user overrides | No current need; either can be added later without changing the model. |

## Consequences

- Every limited entity needs an index that serves its count (`user_id` with `created_at` or `archived_at`).
- Changing a plan role resets the quota window. An admin's manual role change does too, as a deliberate action.
- Cancelling a subscription must not end it before the paid period (step 18); an immediate end would hand out a fresh free quota mid-month.
- Adding a limited entity needs a migration column, its count and a case of the limited entity enum.
- Until step 12 the invoice quota counts by creation time and deleting an invoice returns its place.
- The numeric values of each role are decided with the plans in step 19.

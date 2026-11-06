# PRD: Entity Limits

Date: 2026-11-06. Version: 1.0. Status: draft, awaiting the project owner's approval.
Decision: [D-047](../adr/D-047-entity-limits.md). Related: [PRD: Businesses and contacts](PRD_BUSINESSES_AND_CONTACTS.md) (capacity, archiving, downgrade).

The product decisions were agreed with the project owner on 2026-11-06. Numeric limit values of the plans are not part of this document; they are decided with the plans (TECH_DEBT step 19).

## 1. Problem and goal

A frontend user's plan decides how many invoices, expenses, businesses, contacts and products they may create. The previous system combined several periods, several roles and a separate usage counter, so the effective limit was hard to predict and some limits were not enforced at all.

The goal is a limit system that:

- gives one limit per entity and plan that a user and an admin can read at a glance;
- is enforced the same way on every creation path (frontend, admin, API);
- is configured by an admin without a deploy and extended with a new entity by a small code change.

## 2. Terms

- **Plan role**: the `web` guard role that carries a user's limits (`frontend_user` without a subscription, `frontend_user_s1`, `frontend_user_s2`, …). A frontend user holds exactly one.
- **Capacity**: the maximum number of the user's active records of an entity.
- **Monthly quota**: the maximum number of records of an entity created in the quota window.
- **Quota window**: from the later of the start of the current calendar month and the moment the user's current plan role was assigned, until now.
- **Usage**: the number of records counted against a limit, always a whole number.

## 3. Limited entities

| Entity | Kind of limit | What counts |
|---|---|---|
| Invoice | Monthly quota | Invoices created in the window; once issuing exists (step 12), invoices issued in the window |
| Expense | Monthly quota | Expenses entered in the window |
| Business | Capacity | Active (not archived) businesses |
| Contact | Capacity | Active (not archived) contacts |
| Product | Capacity | Existing products; once products can be archived (step 12), active products |

No other entity is limited. Admin reference data (taxes, banks, payment methods, product categories) are guarded by permissions only.

## 4. Required behaviour

### 4.1 Limit values

- Each plan role has one value per limited entity: a whole number, **unlimited** or **not allowed** (zero).
- A role without configured limits allows no creation of limited entities.
- A user's limits are exactly the limits of their plan role; there are no per-user exceptions and limits of several roles are never combined.

### 4.2 Counting

- Usage is counted from the user's records themselves at the moment of the check.
- Quota time is the system time of the action. A date the user types into the document (issue date, receipt date) does not move a record to another month.
- Deleting a record frees its place in the quota or capacity. Archiving frees a capacity place; restoring takes one, like creating (PRD Businesses and contacts).
- Registration's first business counts against the business capacity.

### 4.3 Changing the plan

- A new plan role applies immediately.
- The quota window restarts at the change: the user gets the full quota of the new plan for the rest of the month, then the calendar month again. This applies to upgrades, downgrades and the return to `frontend_user` at the end of a subscription.
- Capacity is compared with the current active records under the new limit. A downgrade never deletes, archives or locks existing records; it only blocks creating and restoring above the new capacity.

### 4.4 Reaching a limit

- Creating or restoring a record above the limit is refused with a translated message that names the limit and links to the plans.
- The user sees their usage per entity ("8 / 10 invoices this month", "3 / 5 businesses") on the dashboard and on the create form. Create buttons stay visible; the refusal comes from the server.
- A record created by an admin for a user counts against that user's limit. An admin has no way to exceed a user's limit except by changing the user's plan role.

### 4.5 Administration

- An admin edits the limits of each plan role in a dedicated "Role limits" screen: one form per role with a field per limited entity (number, unlimited, not allowed). Only `web` guard roles are offered.
- An admin sees a user's usage and limits, read-only, on the user's detail.
- Access to the screen is governed by the permissions `can_view_role_limit`, `can_create_role_limit`, `can_update_role_limit` and `can_delete_role_limit`.

### 4.6 Plans

- Each subscription plan points to one plan role; plans of different billing periods may share a role.
- Activating a subscription assigns its role; the end of the subscription assigns `frontend_user`.
- The plan pages show the limits generated from the plan role. Manual plan features describe only what limits and permissions cannot express and contain no limit values.

### 4.7 Abuse protection

- Creation is rate limited per user, and the guest invoice (never stored, no plan limit) per IP address.
- The rate limits are the same for all plans; their values are application configuration (`config/billex.php`).

## 5. Exclusions

- Numeric limit values of the plans (step 19).
- Warnings before reaching a limit (for example at 80 %) and limit e-mails.
- Per-user limit exceptions, add-on packages and combining several roles.
- Daily, weekly or yearly limits; limits on amounts or file sizes.
- Limits for guests and for admin reference data.
- Payments, proration and the moment a cancelled subscription ends (step 18).

## 6. Acceptance criteria

| ID | Criterion |
|---|---|
| A01 | A user whose role has no configured limits cannot create an invoice, expense, business, contact or product. |
| A02 | A limit of `0` refuses creation; an unlimited value allows any number. |
| A03 | With an invoice quota of N, the (N+1)-th invoice in the window is refused; the first invoice of the next calendar month is allowed. |
| A04 | An invoice whose user-entered date lies in the previous month counts in the month it was created (issued after step 12). |
| A05 | After a plan role change in the middle of a month, invoices created before the change do not count against the new quota. |
| A06 | Deleting an expense frees a place in the expense quota. |
| A07 | With a business capacity of N, creating or restoring the (N+1)-th active business is refused; archiving one allows the next. |
| A08 | Registration's first business counts against the business capacity. |
| A09 | A downgrade below the current number of active contacts keeps all contacts usable and editable and refuses creating a new one. |
| A10 | A record an admin creates for a user counts against that user's limit and is refused above it, whatever the admin's permissions. |
| A11 | Two simultaneous creations at the last free place result in exactly one record. |
| A12 | The refusal message is translated in all four locales and links to the plans. |
| A13 | The dashboard and the create form show usage and limit per entity; unlimited is shown as such. |
| A14 | An admin can set number, unlimited or not allowed per entity for a `web` role, and sees a user's usage on their detail. |
| A15 | Activating a subscription assigns the plan's role; its end assigns `frontend_user` (step 19). |
| A16 | The plan pages show limit values taken from the plan role (step 19). |
| A17 | Creating faster than the configured rate limit is refused with HTTP 429, for users and for guest invoices. |

## 7. Delivery

- **Step 10 (entity limit conversion):** sections 3, 4.1–4.5 and 4.7; A01–A14 and A17. A04 and the issued-invoice part of section 3 follow step 12.
- **Step 19 (subscription plans):** section 4.6; A15–A16 and the numeric values.

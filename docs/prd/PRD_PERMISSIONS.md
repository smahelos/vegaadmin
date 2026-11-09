# PRD: Permissions

Date: 2026-11-09. Version: 1.1. Status: approved product specification (project owner, 2026-11-09).
Decision: [D-048](../adr/D-048-permission-checks.md). Related: [PRD: Entity limits](PRD_ENTITY_LIMITS.md), [PRD: Businesses and contacts](PRD_BUSINESSES_AND_CONTACTS.md). The code standard is in `.claude/rules/permissions.md`, the conversion is tracked in `docs/TECH_DEBT.md` (step 11).

This document states what users and admins can observe. How the checks are built is decided in D-048.

## 1. Problem and goal

Permissions and record ownership are checked in several places, and which places depends on the entry point. The same action can be protected on one path and unprotected on another: three admin log screens have no permission at all, and some admin writes rely on a check that exists only for HTTP. The same ability also has a different name on the frontend and in the admin.

The goal is a permission system where:

- an action gives the same answer on every entry point (frontend, Livewire, admin, API, console);
- a new action cannot be delivered without stating who may run it;
- one ability has one name, and what a screen offers always matches what the action allows.

## 2. Terms

- **Side**: the frontend (`web` guard) or the admin (`backpack` guard). Permissions and roles are held per side.
- **Permission**: one ability, named `<entity>.<action>` (`contact.create`).
- **Owner**: the user whose data a record is.
- **Actor**: who runs an action: a user, an admin, an automation acting for an owner, the system, or an anonymous visitor.

## 3. Permission names

- One ability has one name on both sides, with no side prefix (`frontend.can_create_contact` and `can_create_contact` become `contact.create`). The name is stored once per side that uses it.
- CRUD abilities are `<entity>.view`, `.create`, `.update` and `.delete`; other actions are `<entity>.<verb>` (`business.archive`, `business.set_default`, `subscription.cancel`, `cron_task.run`).
- Three names are not tied to an entity: `backpack.access` (entry to the admin), `api.access` (entry to the API) and `system.configure` (system settings).
- Renaming changes no one's abilities: every role and user keeps what they had.
- Admin screens that list permissions show the side next to the name (`contact.create (web)`), because the name alone no longer identifies it.

## 4. Required behaviour

### 4.1 Order of the decision

1. Without the permission of the main action the answer is 403, whether the record exists or not, so existence stays hidden. It comes before validation: a user without the permission sees no validation messages.
2. With the permission, a record that is missing or belongs to someone else is a 404. The two cases cannot be told apart.
3. The ownership check runs after validation, so validation messages reveal nothing about another user's records: validation gives the same outcome for another user's record and for a non-existent one.

### 4.2 The two sides stay apart

A permission counts only on its own side. An account holding `invoice.update` on the frontend cannot update an invoice in the admin, and the other way round.

### 4.3 Who reaches what

| Actor | Reaches |
|---|---|
| User | Only their own data, with the permission for the action |
| Admin | Any owner's data, with the permission for the action on the admin side and, for the writes listed below, the owner's permission on the frontend side |
| Automation | One owner's data, with that owner's permissions (arrives with bank connections) |
| System | Only actions declared for the system, such as scheduled work |
| Anonymous | Only actions declared public: the guest invoice and registration |

- On the frontend the owner comes from the sign-in: a user who submits another user's id still acts only on their own data. An admin with the permission selects an existing owner. A call without a user takes the owner from stored data.
- An admin creating or updating a contact, business, product, invoice or expense, or creating a subscription, needs their own permission and the owner must hold the same permission on the frontend side, because the admin can enter only what the owner may have at all. The owner comes from the form, so this refusal follows validation. `expense.create` and `expense.update` exist on the frontend side for this check, and every frontend role holds them. Deleting and archiving do not depend on the owner's permission.
- Content that has no owner and reads the same for everyone (published pages, countries, currencies, banks, payment gateways) needs no permission. Only published pages are available this way.
- The company registry lookup needs no permission and works without signing in, in registration and on the guest invoice too, because it reads a public register and stores nothing. It is rate limited per user, or per IP for an anonymous visitor.

### 4.4 An action that touches another entity

Writing to another entity requires that entity's permission and follows its limit, on top of the permission of the main action. That permission is the owner's. It is checked after validation, and a refusal stores nothing of the whole action. Only reading a related record is covered by the main action's permission.

| Place | Main permission (403 before validation) | Owner's permission for the other entity |
|---|---|---|
| Contact page, new contact | `contact.create` | none |
| New invoice with "save to contacts" ticked | `invoice.create` | `contact.create` |
| Button on an existing invoice's detail | `invoice.update` | `contact.create` |

Registration is an exception: the account, the first business, its address and the optional first bank account are one public entry. It is all-or-nothing, keeps the capacity check and cannot target an existing owner.

### 4.5 What the screens show

- A button, link or menu entry is shown exactly when the actor holds the permission for the action it leads to. A refusal that depends on submitted data or state (the owner's permission, capacity, validation, business state) does not hide it ([PRD: Entity limits](PRD_ENTITY_LIMITS.md), 4.4).
- Hiding is never the protection: a direct request to a hidden action is refused the same way.
- Each admin CRUD operation is available only with its own permission and is otherwise both hidden and refused. An admin write of a record with an owner is checked as a write as well, not only as an operation of the screen.
- The three admin log screens (database maintenance, MySQL optimisation, performance metrics) require `system.configure`.

### 4.6 Changing permissions

A change to a role's or a user's permissions applies from that person's next action, without signing in again.

### 4.7 API

`api.access` grants entry to the API only. Each endpoint then requires the same permission as the matching screen, and every endpoint over an owner's data checks the owner.

### 4.8 Bank accounts

Bank accounts have their own `bank_account.view` and `bank_account.update` (add, edit, archive, restore, default account), whichever party owns them. Every frontend role receives both. The first bank account entered while creating a business remains part of creating that business.

### 4.9 Failed actions

An action that fails stores nothing. The exception is an action that calls outside the database (payment gateway, bank, e-mail, PDF, files): each of its database steps is all-or-nothing, and a step finished before the failure stays, such as the record of a payment attempt.

## 5. Exclusions

- Which frontend roles exist and which plan grants what (step 19); entity limits themselves ([D-047](../adr/D-047-entity-limits.md)).
- Automation for bank connections: the actor is defined here and built with its first use.
- The checks of the database dashboard stay on its form request until step 20; it still requires `system.configure`.
- Permissions that no code checks yet (step 22).

## 6. Acceptance criteria

| ID | Criterion |
|---|---|
| A01 | After the rename every role and user holds the same abilities as before, and role permission counts are unchanged. |
| A02 | A fresh installation has the same permissions and role grants as a migrated one. |
| A03 | A frontend permission does not allow the admin operation of the same name, and an admin permission does not allow the frontend action. |
| A04 | Without the permission of the main action, an action answers 403 on the frontend, in Livewire, in the admin and in the API, for an existing and for a non-existent record alike. |
| A05 | A request with invalid data and without the permission of the main action answers 403 and shows no validation messages. |
| A06 | With the permission and valid input, another user's record and a non-existent record both answer 404. |
| A07 | Validation gives the same outcome for another user's record and a non-existent one, and a validation message never reveals whether another user's record exists. |
| A08 | A user submitting another user's id as the owner acts only on their own data. |
| A09 | An admin with the permission can act on any owner's record; without it the action is refused. |
| A10 | A guest can create a guest invoice and register, and reaches no data of any owner; registration cannot target an existing owner. |
| A11 | A scheduled action runs only where it is declared for the system; the system cannot run any other action. |
| A12 | An unpublished page is not available through public content. |
| A13 | The company registry lookup works without signing in, also in registration and on the guest invoice, and a request above the rate limit is refused. |
| A14 | Saving an invoice's customer to contacts requires `invoice.create` on a new invoice or `invoice.update` on an existing one, and the owner's `contact.create`. Without `contact.create` it is refused and the action stores nothing. It counts against the contact capacity. |
| A15 | A button, link or menu entry is hidden exactly when the actor lacks the permission for its action, and a direct request to it is refused. A refusal by the owner's permission, capacity, validation or business state does not hide it. |
| A16 | An admin CRUD operation without its permission is hidden and refused, also a write with invalid data, and also for businesses, contacts and bank accounts written directly. |
| A17 | The three admin log screens and the database dashboard are refused without `system.configure`. |
| A18 | A permission taken from a role refuses the user's next action, without signing in again. |
| A19 | The API refuses a user without `api.access`, and refuses a user with `api.access` but without the endpoint's permission. |
| A20 | Each bank account action follows `bank_account.view` or `bank_account.update`, independently of the business and contact permissions, and every frontend role holds both. |
| A21 | When a database step of an action fails partway, none of that step's changes are stored; an action that does not call outside the database is one step. |
| A22 | The role form, the user form and the user list filter show each permission with its side. |
| A23 | An admin with the permission creates a record for an existing owner they select; a non-existent owner is refused by validation. |
| A24 | An admin write listed in section 4.3 is refused after validation when the owner lacks the permission on the frontend side, even if the admin holds it. |

## 7. Delivery

All in TECH_DEBT step 11, one PR per phase, in the phases listed there:

- **Phase 1 (names):** section 3; A01, A02, A22.
- **Phases 2–7 (the gate, context by context: Product, Party, User, Invoice, Payment and Analytics, Content and Shared):** sections 4.1–4.7 and 4.9; each phase covers A03–A15, A18, A19, A21, A23 and A24 for its context. Bank accounts (section 4.8, A20) come with Party.
- **Phase 8 (admin):** the admin part of section 4.5; A16 and A17. The database dashboard joins in step 20.

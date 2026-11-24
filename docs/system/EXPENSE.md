# Expense context

Status: maintained map of the code on `main`, last checked 2026-11-24. Start with [the system overview](OVERVIEW.md). The parties an expense stores are described in [PARTY.md](PARTY.md#parties-on-documents), the entity limit in [USER.md](USER.md#entity-limits), money in `rules/domain.md`. The planned model of a received document (document type, VAT lines, payment state instead of the approval statuses) is step 13 of [TECH_DEBT](../TECH_DEBT.md#step-13-expenses-as-received-documents); until then an expense is one amount with an optional tax amount.

The documents the user receives and pays: supplier, own business, date, amount, category, status and attachments. Expenses are entered only in the admin; the frontend shows them only in the dashboard chart (Analytics). Composition root: `ExpenseServiceProvider`.

## Entities

| Entity | Table | Code |
|---|---|---|
| Expense | `expenses`, owned by a user; the parties and the account stored on the row | `App\Models\Expense`, `ExpenseDTO` (only the id, owner, parties and attachments), `ExpenseId`, `ExpenseWriteData`; ports `ExpenseReadRepositoryInterface` and `ExpenseWriteRepositoryInterface` (`EloquentExpenseRepository`, mapper `EloquentExpenseMapper`) |
| Expense category | `expense_categories` (reference data without an owner, the same for every user) | `App\Models\ExpenseCategory`; no DTO, no port |
| Expense status | `statuses` of the `expense-statuses` category (draft, in review, approved, rejected, paid, cancelled), shared with the status admin | `App\Models\Status`; seeded by `ReferenceDataSeeder` |

Values: `amount` and `tax_amount` are `Money` in `ExpenseWriteData`, stored as decimals with the `currency` column. `tax_included` says whether the amount already contains the tax. The form's `tax_rate` choice is not stored: it only lets `public/assets/js/admin/forms/expense-tax-calculator.js` fill the tax amount. `ExpenseNotFoundException` answers 404 (`ExceptionHandling`).

## Use cases

Commands in `app/Application/Expense/Commands/`; there are no queries. Everything runs in the admin, `Admin\ExpenseCrudController` (`expense` CRUD route in `routes/backpack/custom.php`).

| Use case | Admin | Access | Limit, rate limit |
|---|---|---|---|
| `CreateExpense` | `store` (`throttle:create`) | `expense.create` with owner | expenses (monthly quota) |
| `UpdateExpense` | `update` | `expense.update` with owner | — |
| delete | Backpack's delete operation, through the model | `expense.delete` (admin only) | — |
| list, show | Backpack's list and show operations, reading the model | `expense.view` (admin only) | — |

"With owner" is `Access::permissionWithOwner()` (`rules/permissions.md`): the admin's permission and the owner's `web` permission of the same name, which every frontend role holds although no frontend page writes an expense. `CreateExpenseHandler` calls `EntityLimit::assertCanCreate()` for `LimitedEntity::EXPENSE`; an expense is a monthly quota, so deleting one gives its place back (D-047). The `create` rate limiter is registered in `RouteServiceProvider`. Both Commands store files outside the database, so they implement `CallsOutsideDatabaseInterface` and open their own transaction.

Expense categories have no use cases: `Admin\ExpenseCategoryCrudController` writes them through the model, with the `expense.*` permissions ("Product and expense categories share their entity's permissions" in [TECH_DEBT](../TECH_DEBT.md)); `ExpenseCategoryRequest` validates them, and `RestrictedDeleteOperation` answers a category used by an expense with a notification.

## Creating and updating an expense

| Step | Code |
|---|---|
| Form fields | `ExpenseFormFields` (the party fields from `AdminDocumentFormFields`; the owner is a field, disabled on edit), the list filters from `ExpenseFormFields` and `StatusFormFields` |
| Validation | `Admin\ExpenseRequest`: business and contact of the chosen owner, an account of the chosen contact, the supplier fields through `AdminDocumentPartyInput`, a one-off account through `DocumentPartyInput`, a status of the `expense-statuses` category, attachments up to 10 MB |
| Request to Command | `toWriteData()` gives `ExpenseWriteData` with the uploads as `IncomingFile`; `owner()` is the chosen `user_id`, on update the stored owner |
| Handler | `CreateExpenseHandler`, `UpdateExpenseHandler`; a contact or an expense of another owner is refused with `ContactNotFoundException` or `ExpenseNotFoundException` |
| Parties | `ExpensePartiesAssembler`: the own business becomes the customer, the contact or the one-off supplier the supplier; the account is a bank account of that contact or one-off `account_*` fields ([PARTY.md](PARTY.md#parties-on-documents)) |
| Files | `ExpenseAttachments` stores the new attachments in `expenses/attachments` on the `public` disk ("Expense attachments are publicly reachable" in [TECH_DEBT](../TECH_DEBT.md)) and deletes the removed ones after the commit, the rolled-back uploads otherwise; `DocumentLogoService` copies the supplier logo of the contact or the uploaded one |
| Storage | `EloquentExpenseRepository`: the owner comes from the scope, never from the data |

An expense is the opposite of an invoice: the user's business is the customer and the counterparty the supplier, so `ContactIdentityChangedException` points to `supplier_company_number` on the expense routes (`ExceptionHandling`).

Deleting an expense runs no use case: Backpack deletes the row, and its attachments and supplier logo stay on the disk ("Deleting an expense bypasses a use case" in [TECH_DEBT](../TECH_DEBT.md)). Nothing else in Billex refers to an expense, so no foreign key refuses the delete.

## Expenses in statistics and limits

- The dashboard chart "revenue and expenses" sums `expenses.amount` by month through Analytics' `GetRevenueExpenses` (`EloquentApiStatisticsRepository::expenses()`); amounts in different currencies are added together ("Dashboard statistics sum amounts across currencies" in [TECH_DEBT](../TECH_DEBT.md)).
- `EloquentLimitedEntityCounter` counts the expenses created in the quota window; the admin entity usage shows the owner's expense quota (`GetEntityUsage`).

## Events

No Expense use case publishes a domain event and no observer watches expenses ("Move cache invalidation from observers to domain event handlers" in [TECH_DEBT](../TECH_DEBT.md)).

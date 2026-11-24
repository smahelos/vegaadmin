# Analytics context

Status: maintained map of the code on `main`, last checked 2026-11-24. Start with [the system overview](OVERVIEW.md). Analytics owns no records: it reads invoices ([INVOICE.md](INVOICE.md)), expenses ([EXPENSE.md](EXPENSE.md)), businesses and contacts ([PARTY.md](PARTY.md)), subscriptions ([PAYMENT.md](PAYMENT.md)) and users. Permissions are in `rules/permissions.md`.

The user's dashboard with its counts and charts, and the statistics of the admin dashboard over every owner. Composition root: `AnalyticsServiceProvider`.

## Read models

| Read model | Source | Code |
|---|---|---|
| User statistics | counts of the owner's invoices, contacts and businesses, the sum of their invoices | `UserStatisticsDTO`; port `AnalyticsReadRepositoryInterface` (`EloquentAnalyticsRepository`) |
| Customer totals | the owner's invoices grouped by customer (a contact, or a one-off customer by company number or name), then the contacts without an invoice | `CustomerTotalDTO`, `CustomerRevenueQuery`, mapper `EloquentAnalyticsMapper` |
| Chart rows | the owner's invoices and expenses by month, customer, status and payment method | rows as `list<array<string, mixed>>`; port `ApiStatisticsRepository` in Application (`EloquentApiStatisticsRepository`) |
| Platform statistics | counts of every owner's invoices, active businesses and contacts, users and active subscriptions; revenue per currency | `PlatformStatisticsDTO` |
| User activity | the `user_activity_summary` database view (`database/migrations/2026_10_26_000004_count_businesses_and_contacts_in_user_activity.php`) | `App\Models\UserActivitySummary`, `UserActivityStatisticsDTO`, `UserActivityDTO`, `ActivityLevel` (by invoices in the last 30 days) |

The chart period is `StatisticsPeriod` (whole months back from the current month); `StatisticsFilter` holds it with an optional date range, which wins, and the chosen contacts.

## Use cases

Queries in `app/Application/Analytics/Queries/`; there are no Commands. Every Query needs `statistics.view`.

| Use case | Entry | Access | Returns |
|---|---|---|---|
| `GetDashboard` | `Frontend\DashboardController::index()` (`frontend.dashboard`) | `statistics.view`; without it the page renders with no statistics | `DashboardDTO`: user statistics, customer totals of contacts, the formatted total |
| `GetMonthlyRevenue`, `GetCustomerRevenue`, `GetInvoiceStatus`, `GetPaymentMethods`, `GetRevenueExpenses` | `Api\StatisticsController`, the `api.statistics.*` routes in `routes/api.php` | `statistics.view` (`allowed`), `api.access` (`RequireFrontendApiAccess`) | chart rows |
| `GetAdminDashboard` | `Admin\AdminController::dashboard()` (`backpack.dashboard`) | `statistics.view` in the admin guard, over all owners (`Access::allOwners()`); without it the dashboard renders with no statistics | `AdminDashboardDTO`: platform statistics and user activity |

## The user's dashboard

| Step | Code |
|---|---|
| Counts and customer list | `GetDashboardHandler` reads `DashboardService`, which caches the user statistics and the customer totals for 10 minutes under the tag `analytics.user.<id>` (`CacheServiceInterface`); the total is formatted by `MoneyFormatterInterface` |
| Page | `resources/views/frontend/dashboard.blade.php`; the recent invoices and latest parties on it are Invoice's `InvoiceListRecent` and Party's `PartyListLatest` |
| Charts | `resources/js/dashboard-statistics.js` (loaded from `resources/js/app.js`) calls `/api/statistics/<chart>` with the period, dates and contacts; `StatisticsRequest` validates them and builds the `StatisticsFilter` (a year by default) |
| Chart data | the chart handlers read `ApiStatisticsRepository` from the filter's start; `GetRevenueExpensesHandler` merges the revenue and expense rows by month |

Every sum adds amounts in different currencies into one number, and the dashboard total and customer totals call it CZK ("Dashboard statistics sum amounts across currencies" in [TECH_DEBT](../TECH_DEBT.md)). The user statistics and customer totals include invoices without a contact and archived businesses and contacts, which keeps the meaning the metrics had before archiving (A22 in [the party PRD](../prd/PRD_BUSINESSES_AND_CONTACTS.md)); the platform statistics count only active ones.

## The admin dashboard

`GetAdminDashboardHandler` reads the platform statistics (recent counts from 30 days back, revenue per currency in total and from the start of the month) and the five most active users, both through `AnalyticsReadRepositoryInterface`. `AdminDashboardPresenter` formats the revenue, the activity rate, the last invoice date and the colour of the activity level for `resources/views/vendor/backpack/ui/dashboard.blade.php`. The admin dashboard has no charts ("Admin dashboard has no charts" in [TECH_DEBT](../TECH_DEBT.md)).

## Events

Analytics publishes no events; it clears the owner's cached dashboard (`DashboardService::invalidateUserCache()`) when the data change, wired in `EventServiceProvider`:

| Event | Published by | Listener |
|---|---|---|
| Party events (`PartyEvent`) | the Party command handlers | `InvalidateOwnerDashboard` |
| `UserDataChanged` | `InvoiceObserver` | User's `InvalidateUserCache` |

Expenses do not reach the cached dashboard; their only statistics are the uncached revenue-and-expenses chart. The observer is "Move cache invalidation from observers to domain event handlers" in [TECH_DEBT](../TECH_DEBT.md).

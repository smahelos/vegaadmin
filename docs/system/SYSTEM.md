# System context

Status: maintained map of the code on `main`, last checked 2026-11-25. Start with [the system overview](OVERVIEW.md). Permissions are in `rules/permissions.md`; the payment reminders a cron task runs are in [INVOICE.md](INVOICE.md).

The admin screens that run the application rather than the business: cron tasks and the scheduler, clearing the application caches, and database maintenance. Composition root: `SystemServiceProvider`. Cron tasks and caches follow the standard; database maintenance predates it and works through models, `DB::` and Artisan calls in the controller and the console commands ("Step 20: System admin screens" in [TECH_DEBT](../TECH_DEBT.md)).

## Entities

| Entity | Table | Code |
|---|---|---|
| Cron task | `cron_tasks` (`database/migrations/2026_10_25_000012_create_console_tables.php`) | `App\Models\CronTask`, `CronTaskDTO` with `CronTaskId`, `ScheduledCommand` (the commands a task may run, stored as the command name), `CronTaskFrequency`, `CommandOptions`; ports `CronTaskReadRepositoryInterface`, `CronTaskWriteRepositoryInterface` (`EloquentCronTaskRepository`) |
| Maintenance log | `database_maintenance_logs` | `App\Models\DatabaseMaintenanceLog` |
| Performance metric | `performance_metrics` | `App\Models\PerformanceMetric` |
| MySQL optimization log | `mysql_optimization_logs` | `App\Models\MysqlOptimizationLog` |
| Health metric and alert | `database_health_metrics`, `database_health_alerts` | `App\Models\DatabaseHealthMetric`, `App\Models\DatabaseHealthAlert` |

The maintenance tables are created in `database/migrations/2026_10_25_000014_create_database_maintenance_tables.php`. Its archive tables are dropped again: issued documents stay in the main tables (D-050). The cron tasks of a fresh database come from `CronTasksSeeder` (`invoices:check-payment-status` with its default options).

## Use cases

Commands in `app/Application/System/Commands/`, Queries in `app/Application/System/Queries/`. The database maintenance screens have no use cases.

| Use case | Entry | Access | Does |
|---|---|---|---|
| `ClearCache` | `Admin\CacheManagementController`, the `admin.cache.*` routes | `system.configure` | clears the chosen `CacheKind` caches through `CacheClearerInterface` (`ArtisanCacheClearer`) |
| `RunCronTask` | `Admin\CronTaskCrudController::run()` (`admin.cron-tasks.run`, the `run_cron` button) | `cron_task.run` | runs the task's command now through `CommandRunnerInterface` (`ArtisanCommandRunner`) and stores the time and output, also of a failed run |
| `ListActiveCronTasks` | `routes/console.php` | `Actor::system()` | the active tasks the scheduler registers, without those whose stored command or frequency the enums no longer offer |
| `RecordCronTaskRun` | `routes/console.php`, after each scheduled run | `Actor::system()` | stores the time and output of the run |

`ClearCache` and `RunCronTask` implement `CallsOutsideDatabaseInterface`: the gate opens no transaction around them, and `RunCronTaskHandler` stores the run in its own.

## Cron tasks

| Step | Code |
|---|---|
| Admin screen | `Admin\CronTaskCrudController`: list, detail, create, update and delete by the `cron_task.*` permissions; `CronTaskRequest` accepts only a `ScheduledCommand` and options `CommandOptions` can parse |
| Schedule | `routes/console.php` reads `ListActiveCronTasks` and registers each task with `Illuminate\Support\Facades\Schedule` by its frequency; a task with invalid options is logged and skipped. The `cron` container runs `php artisan schedule:work` |
| Output | each scheduled run appends to `storage/logs/cron-<id>.log`, and the after-callback stores that whole file as the task's last output |
| Next run | the list column comes from `CronTask::getNextRunDate()`; `cron:test` (`TestCronExpression`) prints the expression and next run of a task |

The schedule is computed three times (the model, `TestCronExpression`, `routes/console.php`), the output log is never truncated, and the form shows every schedule field for every frequency: "Cron task schedule computed in the model", "Cron task output grows without limit" and "Cron task form ignores `depends`" in [TECH_DEBT](../TECH_DEBT.md).

## Caches

The Cache menu in `resources/views/vendor/backpack/ui/inc/menu_items.blade.php`, shown with `system.configure`, posts to the `admin.cache.*` routes; each clears one `CacheKind` (`cache:clear`, `config:clear`, `view:clear`, `route:clear`) or all of them. A failure flashes an error and is logged. The dashboard and form-option caches the contexts keep are cleared by their own listeners (OVERVIEW, Events), not here.

## Database maintenance

Every screen needs `system.configure`: the menu, the CRUD screens (`DatabaseMaintenanceLogCrudController`, `PerformanceMetricCrudController`, `MysqlOptimizationLogCrudController`) and `DatabaseDashboardController`, whose `DatabaseDashboardRequest::authorize()` asks the gate's `holds()`.

| Part | Code |
|---|---|
| Dashboard | `DatabaseDashboardController::index()` (`admin.database.dashboard`) reads the models into `resources/views/admin/database/dashboard.blade.php`: database size, recent maintenance tasks, the performance chart and health |
| Dashboard actions | `public/assets/js/admin/database-dashboard.js` posts to `admin.database.run-command` with `optimize`, `monitor`, `health-check`, `get-trends`, `clean-metrics` or `metrics-stats`, and to `database-dashboard/resolve-alert`; the controller calls the commands below through Artisan |
| `db:optimize` (`DatabaseOptimizeCommand`) | `OPTIMIZE`, `ANALYZE` and `REPAIR TABLE`; logs to `database_maintenance_logs` and `performance_metrics` |
| `db:monitor` (`DatabaseMonitorCommand`) | table sizes, indexes, slow queries and connections; stores them in `performance_metrics` with `--store` |
| `db:health-check` (`DatabaseHealthCheckCommand`) | connection, cache and buffer pool status into `database_health_metrics`, alerts into `database_health_alerts` |
| `db:clean-metrics` (`CleanPerformanceMetrics`) | deletes old and duplicate rows of `performance_metrics` |

Known faults of these screens are in "Step 20: System admin screens" and the conversion notes of "Step 21: Conversion to the standard" in [TECH_DEBT](../TECH_DEBT.md): invented sample metrics, Chart.js without a pinned version, hand-built list columns.

## Events

System publishes no events and listens to none.

<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Console\Enums\ScheduledCommand;
use App\Domain\User\Enums\Permission;
use App\Http\Requests\Admin\CronTaskRequest;
use App\Http\Requests\Admin\RunCronTaskRequest;
use App\Models\CronTask;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Prologue\Alerts\Facades\Alert;
use Throwable;

class CronTaskCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use GatesCrudOperations;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    public function setup(): void
    {
        CRUD::setModel(CronTask::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/cron-task',
        );
        CRUD::setEntityNameStrings(
            __('cron_tasks.entity.singular'),
            __('cron_tasks.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => Permission::CRON_TASK_VIEW,
                'show' => Permission::CRON_TASK_VIEW,
                'create' => Permission::CRON_TASK_CREATE,
                'update' => Permission::CRON_TASK_UPDATE,
                'delete' => Permission::CRON_TASK_DELETE,
            ],
        );
    }

    protected function setupListOperation(): void
    {
        $column = CRUD::column('name');
        $column->label(
            __('cron_tasks.fields.name'),
        );
        $column = CRUD::column('command');
        $column->label(
            __('cron_tasks.fields.command'),
        );
        $column->type('enum');
        $column = CRUD::column('parameters');
        $column->label(
            __('cron_tasks.fields.parameters'),
        );
        $column = CRUD::column('frequency');
        $column->label(
            __('cron_tasks.fields.frequency'),
        );
        $column->type('select_from_array');
        $column->options(
            [
                'daily' => __('cron_tasks.frequencies.daily'),
                'weekly' => __('cron_tasks.frequencies.weekly'),
                'monthly' => __('cron_tasks.frequencies.monthly'),
                'custom' => __('cron_tasks.frequencies.custom'),
            ],
        );
        $column = CRUD::column('next_run');
        $column->label(
            __('cron_tasks.fields.next_run'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                if ($entry !== null) {
                    $nextRun = $entry->getNextRunDate();
                } else {
                    $nextRun = null;
                }

                return $nextRun ? $nextRun->format('Y-m-d H:i:s') . ' - (' . $nextRun->diffForHumans() . ')' : '-';
            },
        );
        $column = CRUD::column('is_active');
        $column->label(
            __('cron_tasks.fields.is_active'),
        );
        $column->type('boolean');
        $column = CRUD::column('last_run');
        $column->label(
            __('cron_tasks.fields.last_run'),
        );
        $column->type('datetime');

        CRUD::addButton(
            'line',
            'run_cron',
            'view',
            'admin.buttons.run_cron',
            'end',
        );
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(CronTaskRequest::class);

        $field = CRUD::field('name');
        $field->label(
            __('cron_tasks.fields.name'),
        );
        $field->type('text');
        $field->tab(
            __('cron_tasks.tabs.basic'),
        );

        // The command name is a fixed identifier, so it is shown as it is
        $commands = [];
        foreach (ScheduledCommand::cases() as $command) {
            $commands[$command->value] = $command->value;
        }
        $field = CRUD::field('command');
        $field->label(
            __('cron_tasks.fields.command'),
        );
        $field->type('enum');
        $field->options($commands);
        $field->allows_null(false);
        $field->wrapper([
            'class' => 'form-group col-md-6',
        ]);
        $field->tab(
            __('cron_tasks.tabs.basic'),
        );

        $field = CRUD::field('parameters');
        $field->label(
            __('cron_tasks.fields.parameters'),
        );
        $field->type('text');
        $field->hint(
            __('cron_tasks.hints.parameters'),
        );
        $field->wrapper([
            'class' => 'form-group col-md-6',
        ]);
        $field->tab(
            __('cron_tasks.tabs.basic'),
        );

        $field = CRUD::field('frequency');
        $field->label(
            __('cron_tasks.fields.frequency'),
        );
        $field->type('select_from_array');
        $field->options(
            [
                'daily' => __('cron_tasks.frequencies.daily'),
                'weekly' => __('cron_tasks.frequencies.weekly'),
                'monthly' => __('cron_tasks.frequencies.monthly'),
                'custom' => __('cron_tasks.frequencies.custom'),
            ],
        );
        $field->tab(
            __('cron_tasks.tabs.schedule'),
        );

        $field = CRUD::field('run_at');
        $field->label(
            __('cron_tasks.fields.run_at'),
        );
        $field->type('datepicker');
        $field->datepicker_options(['onlyTimepicker' => true]);
        $field->tab(
            __('cron_tasks.tabs.schedule'),
        );
        $field->depends('frequency', [
            'daily',
            'weekly',
            'monthly',
        ]);

        $field = CRUD::field('day_of_week');
        $field->label(
            __('cron_tasks.fields.day_of_week'),
        );
        $field->type('select_from_array');
        $field->options(
            [
                0 => __('cron_tasks.days.sunday'),
                1 => __('cron_tasks.days.monday'),
                2 => __('cron_tasks.days.tuesday'),
                3 => __('cron_tasks.days.wednesday'),
                4 => __('cron_tasks.days.thursday'),
                5 => __('cron_tasks.days.friday'),
                6 => __('cron_tasks.days.saturday'),
            ],
        );
        $field->tab(
            __('cron_tasks.tabs.schedule'),
        );
        $field->depends('frequency', 'weekly');

        $field = CRUD::field('day_of_month');
        $field->label(
            __('cron_tasks.fields.day_of_month'),
        );
        $field->type('number');
        $field->attributes(['min' => 1, 'max' => 31]);
        $field->tab(
            __('cron_tasks.tabs.schedule'),
        );
        $field->depends('frequency', 'monthly');

        $field = CRUD::field('custom_expression');
        $field->label(
            __('cron_tasks.fields.custom_expression'),
        );
        $field->type('text');
        $field->hint(
            __('cron_tasks.hints.custom_expression'),
        );
        $field->tab(
            __('cron_tasks.tabs.schedule'),
        );
        $field->depends('frequency', 'custom');

        $field = CRUD::field('is_active');
        $field->label(
            __('cron_tasks.fields.is_active'),
        );
        $field->type('checkbox');
        $field->default(true);
        $field->tab(
            __('cron_tasks.tabs.basic'),
        );

        $field = CRUD::field('description');
        $field->label(
            __('cron_tasks.fields.description'),
        );
        $field->type('textarea');
        $field->tab(
            __('cron_tasks.tabs.basic'),
        );

        $field = CRUD::field('last_run');
        $field->label(
            __('cron_tasks.fields.last_run'),
        );
        $field->type('datepicker');
        $field->datepicker_options(['timepicker' => true]);
        $field->attributes(['readonly' => 'readonly']);
        $field->tab(
            __('cron_tasks.tabs.history'),
        );
        $field->wrapper(['class' => 'form-group col-md-6']);

        $field = CRUD::field('last_output');
        $field->label(
            __('cron_tasks.fields.last_output'),
        );
        $field->type('textarea');
        $field->attributes(
            [
                'readonly' => 'readonly',
                'class' => 'form-control',
                'rows' => 10,
            ],
        );
        $field->tab(
            __('cron_tasks.tabs.history'),
        );
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation(): void
    {
        $this->setupListOperation();

        $column = CRUD::column('custom_expression');
        $column->label(
            __('cron_tasks.fields.custom_expression'),
        );
        $column = CRUD::column('run_at');
        $column->label(
            __('cron_tasks.fields.run_at'),
        );
        $column = CRUD::column('day_of_week');
        $column->label(
            __('cron_tasks.fields.day_of_week'),
        );
        $column->type('select_from_array');
        $column->options(
            [
                0 => __('cron_tasks.days.sunday'),
                1 => __('cron_tasks.days.monday'),
                2 => __('cron_tasks.days.tuesday'),
                3 => __('cron_tasks.days.wednesday'),
                4 => __('cron_tasks.days.thursday'),
                5 => __('cron_tasks.days.friday'),
                6 => __('cron_tasks.days.saturday'),
            ],
        );
        $column = CRUD::column('day_of_month');
        $column->label(
            __('cron_tasks.fields.day_of_month'),
        );
        $column = CRUD::column('description');
        $column->label(
            __('cron_tasks.fields.description'),
        );
        $column = CRUD::column('last_output');
        $column->label(
            __('cron_tasks.fields.last_output'),
        );
    }

    public function run(RunCronTaskRequest $request, int $id): RedirectResponse
    {
        $task = CronTask::findOrFail($id);

        try {
            // The task stores a failure in last_output and reports it as false; only an Error escapes
            $succeeded = $task->simulateRun();
        } catch (Throwable $e) {
            Log::error('Cron task run failed', [
                'cronTaskId' => $id,
                'exception' => $e,
            ]);
            $succeeded = false;
        }

        if ($succeeded) {
            $alerts = Alert::success(
                __('cron_tasks.messages.task_executed'),
            );
            $alerts->flash();
        } else {
            $alerts = Alert::error(
                __('cron_tasks.messages.execution_failed'),
            );
            $alerts->flash();
        }

        $redirector = redirect();

        return $redirector->back();
    }
}

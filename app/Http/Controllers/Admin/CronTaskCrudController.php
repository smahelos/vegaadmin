<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CronTaskRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Prologue\Alerts\Facades\Alert;
use App\Domain\Shared\Console\Contracts\ArtisanCommandsServiceInterface;
use Illuminate\Support\Facades\Log;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class CronTaskCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\CronTask::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/cron-task');
        CRUD::setEntityNameStrings(__('cron_tasks.entity.singular'), __('cron_tasks.entity.plural'));
    }

    protected function setupListOperation()
    {
        CRUD::column('name')->label(__('cron_tasks.fields.name'));
        CRUD::column('command')->label(__('cron_tasks.fields.command'));
        CRUD::column('frequency')->label(__('cron_tasks.fields.frequency'))
            ->type('select_from_array')
            ->options([
                'daily' => __('cron_tasks.frequencies.daily'),
                'weekly' => __('cron_tasks.frequencies.weekly'),
                'monthly' => __('cron_tasks.frequencies.monthly'),
                'custom' => __('cron_tasks.frequencies.custom'),
            ]);
        CRUD::column('next_run')
            ->label(__('cron_tasks.fields.next_run'))
            ->type('closure')
            ->function(function($entry) {
                if (null !== $entry) {
                    $nextRun = $entry->getNextRunDate();
                } else {
                    $nextRun = null;
                }
                return $nextRun ? $nextRun->format('Y-m-d H:i:s') . ' - (' . $nextRun->diffForHumans() . ')' : '-';
        });
        CRUD::column('is_active')->label(__('cron_tasks.fields.is_active'))
            ->type('boolean');
        CRUD::column('last_run')->label(__('cron_tasks.fields.last_run'))
            ->type('datetime');

        CRUD::addButton('line', 'run_cron', 'view', 'admin.buttons.run_cron', 'end');
    }

    /**
     * @var ArtisanCommandsServiceInterface
     */
    protected ArtisanCommandsServiceInterface $commandsService;

    /**
     * Inject dependencies instead of instantiating directly to respect DDD & DI rules.
     */
    public function __construct(ArtisanCommandsServiceInterface $commandsService)
    {
        parent::__construct();
        $this->commandsService = $commandsService;
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(CronTaskRequest::class);

        CRUD::field('name')->label(__('cron_tasks.fields.name'))
            ->type('text')
            ->tab(__('cron_tasks.tabs.basic'));

    // Prepare commands for select form (cron command category) via injected service
    $commands = $this->commandsService->getCommandsByCategory('cron');

        // Base command
        CRUD::field('base_command')
            ->label(__('cron_tasks.fields.base_command'))
            ->type('select_from_array')
            ->options($commands)
            ->allows_null(false)
            ->wrapper([
                'class' => 'form-group col-md-6'
            ])
            ->tab(__('cron_tasks.tabs.basic'));

        // Command parameters
        CRUD::field('command_params')
            ->label(__('cron_tasks.fields.command_params'))
            ->type('text')
            ->hint(__('cron_tasks.hints.command_params'))
            ->wrapper([
                'class' => 'form-group col-md-6'
            ])
            ->tab(__('cron_tasks.tabs.basic'));

        // Hidden field for complete command
        CRUD::field('command')
            ->type('hidden')
            ->tab(__('cron_tasks.tabs.basic'));

        CRUD::field('frequency')->label(__('cron_tasks.fields.frequency'))
            ->type('select_from_array')
            ->options([
                'daily' => __('cron_tasks.frequencies.daily'),
                'weekly' => __('cron_tasks.frequencies.weekly'),
                'monthly' => __('cron_tasks.frequencies.monthly'),
                'custom' => __('cron_tasks.frequencies.custom'),
            ])
            ->tab(__('cron_tasks.tabs.schedule'));

        CRUD::field('run_at')
            ->label(__('cron_tasks.fields.run_at'))
            ->type('datepicker')
            ->datepicker_options(['onlyTimepicker' => true])
            ->tab(__('cron_tasks.tabs.schedule'))
            ->depends('frequency', ['daily', 'weekly', 'monthly']);

        CRUD::field('day_of_week')
            ->label(__('cron_tasks.fields.day_of_week'))
            ->type('select_from_array')
            ->options([
                0 => __('cron_tasks.days.sunday'),
                1 => __('cron_tasks.days.monday'),
                2 => __('cron_tasks.days.tuesday'),
                3 => __('cron_tasks.days.wednesday'),
                4 => __('cron_tasks.days.thursday'),
                5 => __('cron_tasks.days.friday'),
                6 => __('cron_tasks.days.saturday'),
            ])
            ->tab(__('cron_tasks.tabs.schedule'))
            ->depends('frequency', 'weekly');

        CRUD::field('day_of_month')
            ->label(__('cron_tasks.fields.day_of_month'))
            ->type('number')
            ->attributes(['min' => 1, 'max' => 31])
            ->tab(__('cron_tasks.tabs.schedule'))
            ->depends('frequency', 'monthly');

        CRUD::field('custom_expression')
            ->label(__('cron_tasks.fields.custom_expression'))
            ->type('text')
            ->hint(__('cron_tasks.hints.custom_expression'))
            ->tab(__('cron_tasks.tabs.schedule'))
            ->depends('frequency', 'custom');

        CRUD::field('is_active')->label(__('cron_tasks.fields.is_active'))
            ->type('checkbox')
            ->default(true)
            ->tab(__('cron_tasks.tabs.basic'));

        CRUD::field('description')->label(__('cron_tasks.fields.description'))
            ->type('textarea')
            ->tab(__('cron_tasks.tabs.basic'));

        CRUD::field('last_run')
            ->label(__('cron_tasks.fields.last_run'))
            ->type('datepicker')
            ->datepicker_options(['timepicker' => true])
            ->attributes(['readonly' => 'readonly'])
            ->tab(__('cron_tasks.tabs.history'))
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('last_output')
            ->label(__('cron_tasks.fields.last_output'))
            ->type('textarea')
            ->attributes(
                [
                    'readonly' => 'readonly',
                    'class' => 'form-control',
                    'rows' => 10,
                ]
            )
            ->tab(__('cron_tasks.tabs.history'));

        // Add a field for the command script
        $this->crud->addField([
            'name' => 'command_script',
            'type' => 'view',
            'view' => 'admin.cron_task.script',
            'tab' => __('cron_tasks.tabs.basic'),
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::column('custom_expression')
            ->label(__('cron_tasks.fields.custom_expression'));
        CRUD::column('run_at')
            ->label(__('cron_tasks.fields.run_at'));
        CRUD::column('day_of_week')
            ->label(__('cron_tasks.fields.day_of_week'))
            ->type('select_from_array')
            ->options([
                0 => __('cron_tasks.days.sunday'),
                1 => __('cron_tasks.days.monday'),
                2 => __('cron_tasks.days.tuesday'),
                3 => __('cron_tasks.days.wednesday'),
                4 => __('cron_tasks.days.thursday'),
                5 => __('cron_tasks.days.friday'),
                6 => __('cron_tasks.days.saturday'),
            ]);
        CRUD::column('day_of_month')
            ->label(__('cron_tasks.fields.day_of_month'));
        CRUD::column('description')
            ->label(__('cron_tasks.fields.description'));
        CRUD::column('last_output')
            ->label(__('cron_tasks.fields.last_output'))
            ->escaped(false);
    }

    protected function setupOperationButtons()
    {
        CRUD::operation('list', function() {
            CRUD::addButton('line', 'run_cron', 'view', 'admin.buttons.run_cron', 'end');
        });
    }

    public function runCronTask($id)
    {
        $task = \App\Models\CronTask::findOrFail($id);

        try {
            $task->simulateRun();
            Alert::success(__('cron_tasks.messages.task_executed'))->flash();
        } catch (\Exception $e) {
            Alert::error(__('cron_tasks.messages.execution_failed') . ': ' . $e->getMessage())->flash();
        }

        return redirect()->back();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CronTaskRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Prologue\Alerts\Facades\Alert;
use App\Services\ArtisanCommandsService;
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
        CRUD::setEntityNameStrings(__('admin.cron_tasks.cron_task'), __('admin.cron_tasks.cron_tasks'));
    }

    protected function setupListOperation()
    {
        CRUD::column('name')->label(__('admin.cron_tasks.fields.name'));
        CRUD::column('command')->label(__('admin.cron_tasks.fields.command'));
        CRUD::column('frequency')->label(__('admin.cron_tasks.fields.frequency'))
            ->type('select_from_array')
            ->options([
                'daily' => __('admin.cron_tasks.frequency.daily'),
                'weekly' => __('admin.cron_tasks.frequency.weekly'),
                'monthly' => __('admin.cron_tasks.frequency.monthly'),
                'custom' => __('admin.cron_tasks.frequency.custom'),
            ]);
        // Přidáme nový sloupec pro zobrazení příštího spuštění
        CRUD::column('next_run')
            ->label(__('admin.cron_tasks.fields.next_run'))
            ->type('closure')
            ->function(function($entry) {
                $nextRun = $entry->getNextRunDate();
                return $nextRun ? $nextRun->format('Y-m-d H:i:s') . ' - (' . $nextRun->diffForHumans() . ')' : '-';
        });
        CRUD::column('is_active')->label(__('admin.cron_tasks.fields.is_active'))
            ->type('boolean');
        CRUD::column('last_run')->label(__('admin.cron_tasks.fields.last_run'))
            ->type('datetime');

        CRUD::addButton('line', 'run_cron', 'view', 'admin.buttons.run_cron', 'end');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(CronTaskRequest::class);

        CRUD::field('name')->label(__('admin.cron_tasks.fields.name'))
            ->type('text')
            ->tab(__('admin.cron_tasks.tabs.basic'));
        
        // Získáme všechny dostupné příkazy
        $commandsService = new ArtisanCommandsService();
        // Připravíme výchozí příkazy z kategorie 'cron'
        $commands = $commandsService->getCommandsByCategory('cron');
        
        // Pole pro základní příkaz - select
        CRUD::field('base_command')
            ->label(__('admin.cron_tasks.fields.base_command'))
            ->type('select_from_array')
            ->options($commands)
            ->allows_null(false)
            ->wrapper([
                'class' => 'form-group col-md-6'
            ])
            ->tab(__('admin.cron_tasks.tabs.basic'));
        
        // Pole pro parametry příkazu
        CRUD::field('command_params')
            ->label(__('admin.cron_tasks.fields.command_params'))
            ->type('text')
            ->hint(__('admin.cron_tasks.hints.command_params'))
            ->wrapper([
                'class' => 'form-group col-md-6'
            ])
            ->tab(__('admin.cron_tasks.tabs.basic'));
        
        // Skryté pole pro kompletní příkaz
        CRUD::field('command')
            ->type('hidden')
            ->tab(__('admin.cron_tasks.tabs.basic'));
            
        // CRUD::field('command')->label(__('admin.cron_tasks.fields.command'))
        //     ->type('text')
        //     ->tab(__('admin.cron_tasks.tabs.basic'));
        
        CRUD::field('frequency')->label(__('admin.cron_tasks.fields.frequency'))
            ->type('select_from_array')
            ->options([
                'daily' => __('admin.cron_tasks.frequency.daily'),
                'weekly' => __('admin.cron_tasks.frequency.weekly'),
                'monthly' => __('admin.cron_tasks.frequency.monthly'),
                'custom' => __('admin.cron_tasks.frequency.custom'),
            ])
            ->tab(__('admin.cron_tasks.tabs.schedule'));
        
        CRUD::field('run_at')
            ->label(__('admin.cron_tasks.fields.run_at'))
            ->type('time')
            ->tab(__('admin.cron_tasks.tabs.schedule'))
            ->depends('frequency', ['daily', 'weekly', 'monthly']);
        
        CRUD::field('day_of_week')
            ->label(__('admin.cron_tasks.fields.day_of_week'))
            ->type('select_from_array')
            ->options([
                0 => __('admin.cron_tasks.days.sunday'),
                1 => __('admin.cron_tasks.days.monday'),
                2 => __('admin.cron_tasks.days.tuesday'),
                3 => __('admin.cron_tasks.days.wednesday'),
                4 => __('admin.cron_tasks.days.thursday'),
                5 => __('admin.cron_tasks.days.friday'),
                6 => __('admin.cron_tasks.days.saturday'),
            ])
            ->tab(__('admin.cron_tasks.tabs.schedule'))
            ->depends('frequency', 'weekly');
        
        CRUD::field('day_of_month')
            ->label(__('admin.cron_tasks.fields.day_of_month'))
            ->type('number')
            ->attributes(['min' => 1, 'max' => 31])
            ->tab(__('admin.cron_tasks.tabs.schedule'))
            ->depends('frequency', 'monthly');
        
        CRUD::field('custom_expression')
            ->label(__('admin.cron_tasks.fields.custom_expression'))
            ->type('text')
            ->hint(__('admin.cron_tasks.hints.custom_expression') . ' ' . __('admin.cron_tasks.hints.custom_expression_examples'))
            ->tab(__('admin.cron_tasks.tabs.schedule'))
            ->depends('frequency', 'custom');
        
        CRUD::field('is_active')->label(__('admin.cron_tasks.fields.is_active'))
            ->type('checkbox')
            ->default(true)
            ->tab(__('admin.cron_tasks.tabs.basic'));
        
        CRUD::field('description')->label(__('admin.cron_tasks.fields.description'))
            ->type('textarea')
            ->tab(__('admin.cron_tasks.tabs.basic'));
        
        CRUD::field('last_run')
            ->label(__('admin.cron_tasks.fields.last_run'))
            ->type('datetime')
            ->attributes(['readonly' => 'readonly'])
            ->tab(__('admin.cron_tasks.tabs.history'))
            ->wrapper(['class' => 'form-group col-md-6']);
        
        CRUD::field('last_output')
            ->label(__('admin.cron_tasks.fields.last_output'))
            ->type('textarea')
            ->attributes(
                [
                    'readonly' => 'readonly',
                    'class' => 'form-control min-h-200px',
                ]
            )
            ->tab(__('admin.cron_tasks.tabs.history'));

        // Přidáme skript pro spojení polí
        $this->crud->addField([
            'name' => 'command_script',
            'type' => 'view',
            'view' => 'admin.cron_task.script',
            'tab' => __('admin.cron_tasks.tabs.basic'),
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
            ->label(__('admin.cron_tasks.fields.custom_expression'));
        CRUD::column('run_at')
            ->label(__('admin.cron_tasks.fields.run_at'));
        CRUD::column('day_of_week')
            ->label(__('admin.cron_tasks.fields.day_of_week'))
            ->type('select_from_array')
            ->options([
                0 => __('admin.cron_tasks.days.sunday'),
                1 => __('admin.cron_tasks.days.monday'),
                2 => __('admin.cron_tasks.days.tuesday'),
                3 => __('admin.cron_tasks.days.wednesday'),
                4 => __('admin.cron_tasks.days.thursday'),
                5 => __('admin.cron_tasks.days.friday'),
                6 => __('admin.cron_tasks.days.saturday'),
            ]);
        CRUD::column('day_of_month')
            ->label(__('admin.cron_tasks.fields.day_of_month'));
        CRUD::column('description')
            ->label(__('admin.cron_tasks.fields.description'));
        CRUD::column('last_output')
            ->label(__('admin.cron_tasks.fields.last_output'))
            ->escaped(false);
    }

    protected function setupOperationButtons()
    {
        CRUD::operation('list', function() {
            CRUD::addButton('line', 'run_cron', 'view', 'admin.buttons.run_cron', 'end');
        });
    }

    /**
     * Přidejte tuto metodu do třídy kontroléru
     */
    public function runCronTask($id)
    {
        $task = \App\Models\CronTask::findOrFail($id);
        
        try {
            $task->simulateRun();
            Alert::success(__('admin.cron_tasks.messages.task_executed'))->flash();
        } catch (\Exception $e) {
            Alert::error(__('admin.cron_tasks.messages.execution_failed') . ': ' . $e->getMessage())->flash();
        }
        
        return redirect()->back();
    }
}

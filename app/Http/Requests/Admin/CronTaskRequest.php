<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CronTaskRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'base_command' => 'required|string|max:100', // Validace pro základní příkaz
            'command_params' => 'nullable|string|max:255', // Validace pro parametry
            'frequency' => 'required|in:daily,weekly,monthly,custom',
            'run_at' => 'nullable|date_format:H:i',
            'day_of_week' => 'nullable|integer|between:0,6',
            'day_of_month' => 'nullable|integer|between:1,31',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'custom_expression' => [
                'required_if:frequency,custom',
                function ($attribute, $value, $fail) {
                    // Kontrola validního CRON výrazu pomocí regulárního výrazu
                    if ($this->input('frequency') === 'custom') {
                        $pattern = '/^(\*|([0-9]|[1-5][0-9])([\-,\/]([0-9]|[1-5][0-9]))?) (\*|([0-9]|1[0-9]|2[0-3])([\-,\/]([0-9]|1[0-9]|2[0-3]))?) (\*|([1-9]|[12][0-9]|3[01])([\-,\/]([1-9]|[12][0-9]|3[01]))?) (\*|([1-9]|1[0-2])([\-,\/]([1-9]|1[0-2]))?) (\*|([0-6])([\-,\/]([0-6]))?)$/';
                        if (!preg_match($pattern, $value)) {
                            $fail(__('admin.cron_tasks.validation.invalid_cron_expression'));
                        }
                    }
                }
            ],
        ];
    }

    public function attributes()
    {
        return [
            'name' => __('admin.cron_tasks.fields.name'),
            'base_command' => __('admin.cron_tasks.fields.base_command'),
            'command_params' => __('admin.cron_tasks.fields.command_params'),
            'frequency' => __('admin.cron_tasks.fields.frequency'),
            'custom_expression' => __('admin.cron_tasks.fields.custom_expression'),
            'run_at' => __('admin.cron_tasks.fields.run_at'),
            'day_of_week' => __('admin.cron_tasks.fields.day_of_week'),
            'day_of_month' => __('admin.cron_tasks.fields.day_of_month'),
            'is_active' => __('admin.cron_tasks.fields.is_active'),
            'description' => __('admin.cron_tasks.fields.description'),
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('admin.cron_tasks.fields.name')]),
            'command.required' => __('validation.required', ['attribute' => __('admin.cron_tasks.fields.command')]),
            'frequency.required' => __('validation.required', ['attribute' => __('admin.cron_tasks.fields.frequency')]),
        ];
    }
}

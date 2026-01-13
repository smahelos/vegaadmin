<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CronTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only authenticated backpack users with cron task permission
        return backpack_auth()->check() && backpack_auth()->user()->can('can_create_edit_cron_task');
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|min:2|max:255',
            'base_command' => 'required|string|min:2|max:100', // Base command validation
            'command_params' => 'nullable|string|max:255', // Parameters validation
            'frequency' => 'required|in:daily,weekly,monthly,custom',
            'run_at' => 'nullable|date_format:H:i',
            'day_of_week' => 'nullable|integer|between:0,6',
            'day_of_month' => 'nullable|integer|between:1,31',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ];

        // Add conditional rule for custom_expression
        $rules['custom_expression'] = [
            'required_if:frequency,custom',
            function ($attribute, $value, $fail) {
                // Validate custom cron expression only when frequency is custom
                $frequency = $this->input('frequency');
                if ($frequency === 'custom') {
                    // Basic 5-part cron: minute hour day-of-month month day-of-week with ranges, lists, steps
                    $pattern = '/^(\*|([0-5]?\d)([\-,\/][0-5]?\d)*) (\*|([01]?\d|2[0-3])([\-,\/][01]?\d|2[0-3])*) (\*|([1-9]|[12]\d|3[01])([\-,\/][1-9]|[12]\d|3[01])*) (\*|(1[0-2]|0?[1-9])([\-,\/](1[0-2]|0?[1-9]))*) (\*|([0-6])([\-,\/][0-6])*)$/';
                    if (!is_string($value) || !preg_match($pattern, $value)) {
                        $fail(__('admin.cron_tasks.validation.invalid_cron_expression'));
                    }
                }
            }
        ];

        return $rules;
    }

    public function attributes(): array
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

    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('admin.cron_tasks.fields.name')]),
            'name.string' => __('validation.string', ['attribute' => __('admin.cron_tasks.fields.name')]),
            'name.min' => __('validation.min.string', ['attribute' => __('admin.cron_tasks.fields.name'), 'min' => 2]),
            'name.max' => __('validation.max.string', ['attribute' => __('admin.cron_tasks.fields.name'), 'max' => 255]),
            'base_command.required' => __('validation.required', ['attribute' => __('admin.cron_tasks.fields.base_command')]),
            'base_command.string' => __('validation.string', ['attribute' => __('admin.cron_tasks.fields.base_command')]),
            'base_command.min' => __('validation.min.string', ['attribute' => __('admin.cron_tasks.fields.base_command'), 'min' => 2]),
            'base_command.max' => __('validation.max.string', ['attribute' => __('admin.cron_tasks.fields.base_command'), 'max' => 100]),
            'command_params.string' => __('validation.string', ['attribute' => __('admin.cron_tasks.fields.command_params')]),
            'command_params.max' => __('validation.max.string', ['attribute' => __('admin.cron_tasks.fields.command_params'), 'max' => 255]),
            'frequency.required' => __('validation.required', ['attribute' => __('admin.cron_tasks.fields.frequency')]),
            'frequency.in' => __('validation.in', ['attribute' => __('admin.cron_tasks.fields.frequency')]),
            'run_at.date_format' => __('validation.date_format', ['attribute' => __('admin.cron_tasks.fields.run_at'), 'format' => 'H:i']),
            'day_of_week.integer' => __('validation.integer', ['attribute' => __('admin.cron_tasks.fields.day_of_week')]),
            'day_of_week.between' => __('validation.between.numeric', ['attribute' => __('admin.cron_tasks.fields.day_of_week'), 'min' => 0, 'max' => 6]),
            'day_of_month.integer' => __('validation.integer', ['attribute' => __('admin.cron_tasks.fields.day_of_month')]),
            'day_of_month.between' => __('validation.between.numeric', ['attribute' => __('admin.cron_tasks.fields.day_of_month'), 'min' => 1, 'max' => 31]),
            'is_active.boolean' => __('validation.boolean', ['attribute' => __('admin.cron_tasks.fields.is_active')]),
            'description.string' => __('validation.string', ['attribute' => __('admin.cron_tasks.fields.description')]),
            'custom_expression.required_if' => __('validation.required', ['attribute' => __('admin.cron_tasks.fields.custom_expression')]),
        ];
    }
}

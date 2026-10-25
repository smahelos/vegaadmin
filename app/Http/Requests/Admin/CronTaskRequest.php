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
            function (
                $attribute,
                $value,
                $fail,
            ): void {
                // Validate custom cron expression only when frequency is custom
                $frequency = $this->input('frequency');
                if ($frequency === 'custom') {
                    // Basic 5-part cron: minute hour day-of-month month day-of-week with ranges, lists, steps
                    $pattern = '/^(\*|([0-5]?\d)([\-,\/][0-5]?\d)*) (\*|([01]?\d|2[0-3])([\-,\/][01]?\d|2[0-3])*) (\*|([1-9]|[12]\d|3[01])([\-,\/][1-9]|[12]\d|3[01])*) (\*|(1[0-2]|0?[1-9])([\-,\/](1[0-2]|0?[1-9]))*) (\*|([0-6])([\-,\/][0-6])*)$/';
                    if (! is_string($value) || ! preg_match($pattern, $value)) {
                        $fail(__('cron_tasks.validation.cron_expression'));
                    }
                }
            },
        ];

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => __('cron_tasks.fields.name'),
            'base_command' => __('cron_tasks.fields.base_command'),
            'command_params' => __('cron_tasks.fields.command_params'),
            'frequency' => __('cron_tasks.fields.frequency'),
            'custom_expression' => __('cron_tasks.fields.custom_expression'),
            'run_at' => __('cron_tasks.fields.run_at'),
            'day_of_week' => __('cron_tasks.fields.day_of_week'),
            'day_of_month' => __('cron_tasks.fields.day_of_month'),
            'is_active' => __('cron_tasks.fields.is_active'),
            'description' => __('cron_tasks.fields.description'),
        ];
    }

    /**
     * The standard required_if message would name the raw frequency value "custom".
     */
    public function messages(): array
    {
        return [
            'custom_expression.required_if' => __('validation.required'),
        ];
    }
}

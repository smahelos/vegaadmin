<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CronTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // only allow updates if the user is logged in
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|min:2|max:255',
            'command' => 'required|string|max:1000',
            'frequency' => 'required|string|in:daily,weekly,monthly,custom',
            'run_at' => 'nullable|date_format:H:i',
            'day_of_week' => 'nullable|integer|between:0,6',
            'day_of_month' => 'nullable|integer|between:1,31',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string|max:1000',
        ];

        // Conditional cron expression rule mirroring admin complexity (basic 5-part cron validation)
        $rules['custom_expression'] = [
            'nullable', 'required_if:frequency,custom', 'string', 'max:100',
            function ($attribute, $value, $fail) {
                if ($this->input('frequency') === 'custom') {
                    $pattern = '/^(\*|([0-5]?\d)([\-,\/][0-5]?\d)*) (\*|([01]?\d|2[0-3])([\-,\/][01]?\d|2[0-3])*) (\*|([1-9]|[12]\d|3[01])([\-,\/][1-9]|[12]\d|3[01])*) (\*|(1[0-2]|0?[1-9])([\-,\/](1[0-2]|0?[1-9]))*) (\*|([0-6])([\-,\/][0-6])*)$/';
                    if (!is_string($value) || !preg_match($pattern, $value)) {
                        $fail(__('admin.cron_tasks.validation.invalid_cron_expression'));
                    }
                }
            }
        ];

        return $rules;
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => trans('admin.cron_tasks.fields.name'),
            'command' => trans('admin.cron_tasks.fields.command'), // frontend command maps to admin command
            'frequency' => trans('admin.cron_tasks.fields.frequency'),
            'custom_expression' => trans('admin.cron_tasks.fields.custom_expression'),
            'run_at' => trans('admin.cron_tasks.fields.run_at'),
            'day_of_week' => trans('admin.cron_tasks.fields.day_of_week'),
            'day_of_month' => trans('admin.cron_tasks.fields.day_of_month'),
            'is_active' => trans('admin.cron_tasks.fields.is_active'),
            'description' => trans('admin.cron_tasks.fields.description'),
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => trans('admin.cron_tasks.fields.name')]),
            'name.min' => __('validation.min.string', ['attribute' => trans('admin.cron_tasks.fields.name'), 'min' => 2]),
            'name.max' => __('validation.max.string', ['attribute' => trans('admin.cron_tasks.fields.name'), 'max' => 255]),
            'command.required' => __('validation.required', ['attribute' => trans('admin.cron_tasks.fields.command')]),
            'command.max' => __('validation.max.string', ['attribute' => trans('admin.cron_tasks.fields.command'), 'max' => 1000]),
            'frequency.required' => __('validation.required', ['attribute' => trans('admin.cron_tasks.fields.frequency')]),
            'frequency.in' => __('validation.in', ['attribute' => trans('admin.cron_tasks.fields.frequency')]),
            'custom_expression.required_if' => __('validation.required', ['attribute' => trans('admin.cron_tasks.fields.custom_expression')]),
            'custom_expression.max' => __('validation.max.string', ['attribute' => trans('admin.cron_tasks.fields.custom_expression'), 'max' => 100]),
            'run_at.date_format' => __('validation.date_format', ['attribute' => trans('admin.cron_tasks.fields.run_at'), 'format' => 'H:i']),
            'day_of_week.integer' => __('validation.integer', ['attribute' => trans('admin.cron_tasks.fields.day_of_week')]),
            'day_of_week.between' => __('validation.between.numeric', ['attribute' => trans('admin.cron_tasks.fields.day_of_week'), 'min' => 0, 'max' => 6]),
            'day_of_month.integer' => __('validation.integer', ['attribute' => trans('admin.cron_tasks.fields.day_of_month')]),
            'day_of_month.between' => __('validation.between.numeric', ['attribute' => trans('admin.cron_tasks.fields.day_of_month'), 'min' => 1, 'max' => 31]),
            'is_active.boolean' => __('validation.boolean', ['attribute' => trans('admin.cron_tasks.fields.is_active')]),
            'description.max' => __('validation.max.string', ['attribute' => trans('admin.cron_tasks.fields.description'), 'max' => 1000]),
        ];
    }
}

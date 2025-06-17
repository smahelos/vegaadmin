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
    public function authorize()
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
        return [
            'name' => 'required|string|min:2|max:255',
            'command' => 'required|string|max:1000',
            'frequency' => 'required|string|in:daily,weekly,monthly,custom',
            'custom_expression' => 'nullable|required_if:frequency,custom|string|max:100',
            'run_at' => 'nullable|date_format:H:i',
            'day_of_week' => 'nullable|integer|between:0,6',
            'day_of_month' => 'nullable|integer|between:1,31',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => trans('cron_tasks.name'),
            'command' => trans('cron_tasks.command'),
            'frequency' => trans('cron_tasks.frequency'),
            'custom_expression' => trans('cron_tasks.custom_expression'),
            'run_at' => trans('cron_tasks.run_at'),
            'day_of_week' => trans('cron_tasks.day_of_week'),
            'day_of_month' => trans('cron_tasks.day_of_month'),
            'is_active' => trans('cron_tasks.is_active'),
            'description' => trans('cron_tasks.description'),
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
            'name.required' => trans('cron_tasks.validation.name_required'),
            'command.required' => trans('cron_tasks.validation.command_required'),
            'frequency.required' => trans('cron_tasks.validation.frequency_required'),
            'frequency.in' => trans('cron_tasks.validation.frequency_invalid'),
            'custom_expression.required_if' => trans('cron_tasks.validation.custom_expression_required'),
        ];
    }
}

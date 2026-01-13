<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EntityUsageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow system configuration access
        return backpack_auth()->check() && backpack_auth()->user()->can('can_configure_system');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $entityTypes = \App\Models\EntityLimit::ENTITY_TYPES;
        $metricTypes = array_keys(\App\Models\EntityLimit::METRIC_TYPES);
        $periodTypes = array_keys(\App\Models\EntityLimit::PERIOD_TYPES);
        
        return [
            'user_id' => 'nullable|exists:users,id',  // nullable for anonymous users
            'entity_type' => 'required|in:' . implode(',', $entityTypes),
            'metric_type' => 'required|in:' . implode(',', $metricTypes),
            'period_type' => 'required|in:' . implode(',', $periodTypes),
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'current_value' => 'required|numeric|min:0',
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
            'user_id' => __('general.entity_limits.user_id'),
            'entity_type' => __('general.entity_limits.entity_type'),
            'metric_type' => __('general.entity_limits.metric_type'),
            'period_type' => __('general.entity_limits.period_type'),
            'period_start' => __('general.entity_limits.period_start'),
            'period_end' => __('general.entity_limits.period_end'),
            'current_value' => __('general.entity_limits.current_value'),
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
            'user_id.exists' => __('validation.exists', ['attribute' => __('general.entity_limits.user_id')]),
            'entity_type.required' => __('validation.required', ['attribute' => __('general.entity_limits.entity_type')]),
            'entity_type.in' => __('validation.in', ['attribute' => __('general.entity_limits.entity_type')]),
            'metric_type.required' => __('validation.required', ['attribute' => __('general.entity_limits.metric_type')]),
            'metric_type.in' => __('validation.in', ['attribute' => __('general.entity_limits.metric_type')]),
            'period_type.required' => __('validation.required', ['attribute' => __('general.entity_limits.period_type')]),
            'period_type.in' => __('validation.in', ['attribute' => __('general.entity_limits.period_type')]),
            'period_start.required' => __('validation.required', ['attribute' => __('general.entity_limits.period_start')]),
            'period_end.required' => __('validation.required', ['attribute' => __('general.entity_limits.period_end')]),
            'period_end.after_or_equal' => __('validation.after_or_equal', ['attribute' => __('general.entity_limits.period_end'), 'date' => __('general.entity_limits.period_start')]),
            'current_value.required' => __('validation.required', ['attribute' => __('general.entity_limits.current_value')]),
            'current_value.min' => __('validation.min.numeric', ['attribute' => __('general.entity_limits.current_value'), 'min' => 0]),
        ];
    }
}

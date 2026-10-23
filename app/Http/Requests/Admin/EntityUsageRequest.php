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
            'user_id' => __('entity_limits.usages.fields.user_id'),
            'entity_type' => __('entity_limits.usages.fields.entity_type'),
            'metric_type' => __('entity_limits.usages.fields.metric_type'),
            'period_type' => __('entity_limits.usages.fields.period_type'),
            'period_start' => __('entity_limits.usages.fields.period_start'),
            'period_end' => __('entity_limits.usages.fields.period_end'),
            'current_value' => __('entity_limits.usages.fields.current_value'),
        ];
    }
}

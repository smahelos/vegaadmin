<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EntityLimitRequest extends FormRequest
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
        
        // Get the current entity limit ID for updates
        $id = $this->get('id') ?? $this->route('id') ?? $this->route('entity_limit');
        
        return [
            'permission_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('entity_limits', 'permission_name')->ignore($id),
            ],
            'entity_type' => 'required|in:' . implode(',', $entityTypes),
            'metric_type' => 'required|in:' . implode(',', $metricTypes),
            'period_type' => 'required|in:' . implode(',', $periodTypes),
            'limit_value' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     */
    public function attributes(): array
    {
        return [
            'permission_name' => __('entity_limits.fields.permission_name'),
            'entity_type' => __('entity_limits.fields.entity_type'),
            'metric_type' => __('entity_limits.fields.metric_type'),
            'period_type' => __('entity_limits.fields.period_type'),
            'limit_value' => __('entity_limits.fields.limit_value'),
            'description' => __('entity_limits.fields.description'),
            'is_active' => __('entity_limits.fields.is_active'),
        ];
    }
}

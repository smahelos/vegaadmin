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

    /**entity_limits
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'permission_name' => __('general.entity_limits.permission_name', [], app()->getLocale()) ?: 'permission name',
            'entity_type' => __('general.entity_limits.entities.invoice', [], app()->getLocale()) ? __('general.entity_limits.entities.invoice') : 'entity type', // fallback pattern
            'metric_type' => __('general.entity_limits.limit_types.count', [], app()->getLocale()) ?: 'metric type',
            'period_type' => __('general.entity_limits.periods.monthly', [], app()->getLocale()) ?: 'period type',
            'limit_value' => __('general.entity_limits.limit_value', [], app()->getLocale()) ?: 'limit value',
            'description' => __('general.entity_limits.description', [], app()->getLocale()) ?: 'description',
            'is_active' => __('general.entity_limits.is_active', [], app()->getLocale()) ?: 'active status',
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
            'permission_name.required' => __('validation.required', ['attribute' => __('general.entity_limits.permission_name', [], app()->getLocale()) ?: 'permission name']),
            'permission_name.unique' => __('validation.unique', ['attribute' => __('general.entity_limits.permission_name', [], app()->getLocale()) ?: 'permission name']),
            'permission_name.max' => __('validation.max.string', ['attribute' => __('general.entity_limits.permission_name', [], app()->getLocale()) ?: 'permission name', 'max' => 255]),
            'entity_type.required' => __('validation.required', ['attribute' => __('general.entity_limits.entities.invoice', [], app()->getLocale()) ?: 'entity type']),
            'entity_type.in' => __('validation.in', ['attribute' => __('general.entity_limits.entities.invoice', [], app()->getLocale()) ?: 'entity type']),
            'metric_type.required' => __('validation.required', ['attribute' => __('general.entity_limits.limit_types.count', [], app()->getLocale()) ?: 'metric type']),
            'metric_type.in' => __('validation.in', ['attribute' => __('general.entity_limits.limit_types.count', [], app()->getLocale()) ?: 'metric type']),
            'period_type.required' => __('validation.required', ['attribute' => __('general.entity_limits.periods.monthly', [], app()->getLocale()) ?: 'period type']),
            'period_type.in' => __('validation.in', ['attribute' => __('general.entity_limits.periods.monthly', [], app()->getLocale()) ?: 'period type']),
            'limit_value.required' => __('validation.required', ['attribute' => __('general.entity_limits.limit_value', [], app()->getLocale()) ?: 'limit value']),
            'limit_value.min' => __('validation.min.numeric', ['attribute' => __('general.entity_limits.limit_value', [], app()->getLocale()) ?: 'limit value', 'min' => 0]),
            'limit_value.numeric' => __('validation.numeric', ['attribute' => __('general.entity_limits.limit_value', [], app()->getLocale()) ?: 'limit value']),
        ];
    }
}

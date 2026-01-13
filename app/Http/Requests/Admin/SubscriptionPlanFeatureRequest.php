<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class SubscriptionPlanFeatureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return backpack_user() && backpack_user()->hasPermissionTo('can_create_edit_subscription_plan_feature', 'backpack');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Resolve current model ID (works for both implicit model binding and manual id parameter)
        $currentId = optional($this->route('subscription_plan_feature'))->id
            ?? $this->route('id')
            ?? $this->id;

        return [
            'name' => 'required|string|min:3|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plan_features,slug,' . ($currentId ?? 'NULL') . ',id',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('admin.subscription_plan_features.name'),
            'slug' => trans('admin.subscription_plan_features.slug'),
            'description' => trans('admin.subscription_plan_features.description'),
            'is_active' => trans('admin.subscription_plan_features.is_active'),
            'sort_order' => trans('admin.subscription_plan_features.sort_order'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('admin.subscription_plan_features.validation.name_required'),
            'name.string' => trans('admin.subscription_plan_features.validation.name_string'),
            'name.min' => trans('admin.subscription_plan_features.validation.name_min'),
            'name.max' => trans('admin.subscription_plan_features.validation.name_max'),
            'slug.required' => trans('admin.subscription_plan_features.validation.slug_required'),
            'slug.string' => trans('admin.subscription_plan_features.validation.slug_string'),
            'slug.unique' => trans('admin.subscription_plan_features.validation.slug_unique'),
            'description.string' => trans('admin.subscription_plan_features.validation.description_string'),
            'description.max' => trans('admin.subscription_plan_features.validation.description_max'),
            'sort_order.integer' => trans('admin.subscription_plan_features.validation.sort_order_integer'),
            'sort_order.min' => trans('admin.subscription_plan_features.validation.sort_order_min'),
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Generate slug if not provided and name present
        if (empty($this->slug) && !empty($this->name)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}

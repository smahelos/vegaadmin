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
            'name' => __('subscriptions.features.fields.name'),
            'slug' => __('subscriptions.features.fields.slug'),
            'description' => __('subscriptions.features.fields.description'),
            'is_active' => __('subscriptions.features.fields.is_active'),
            'sort_order' => __('subscriptions.features.fields.sort_order'),
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

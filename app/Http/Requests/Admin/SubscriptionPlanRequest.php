<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionPlanRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert JSON string to array if needed
        if ($this->has('features') && is_string($this->features)) {
            $features = json_decode($this->features, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($features)) {
                $this->merge(['features' => $features]);
            }
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow logged in users with proper permissions
        $user = backpack_user();
        
        return $user && $user->hasPermissionTo('can_create_edit_subscription_plan', 'backpack');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'currency' => 'required|string|in:CZK,EUR,USD',
            'billing_period' => 'required|string|in:monthly,yearly',
            'billing_interval' => 'required|integer|min:1|max:12',
            'features' => 'sometimes|array',
            'features.*' => 'integer|exists:subscription_plan_features,id',
            'is_active' => 'boolean',
            'trial_days' => 'required|integer|min:0|max:365',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('admin.subscription_plans.name'),
            'description' => trans('admin.subscription_plans.description'),
            'price' => trans('admin.subscription_plans.price'),
            'currency' => trans('admin.subscription_plans.currency'),
            'billing_period' => trans('admin.subscription_plans.billing_period'),
            'billing_interval' => trans('admin.subscription_plans.billing_interval'),
            'features' => trans('admin.subscription_plans.features'),
            'is_active' => trans('admin.subscription_plans.is_active'),
            'trial_days' => trans('admin.subscription_plans.trial_days'),
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('admin.subscription_plans.validation.name_required'),
            'name.string' => trans('admin.subscription_plans.validation.name_string'),
            'name.min' => trans('admin.subscription_plans.validation.name_min'),
            'name.max' => trans('admin.subscription_plans.validation.name_max'),
            'description.string' => trans('admin.subscription_plans.validation.description_string'),
            'description.max' => trans('admin.subscription_plans.validation.description_max'),
            'price.required' => trans('admin.subscription_plans.validation.price_required'),
            'price.numeric' => trans('admin.subscription_plans.validation.price_numeric'),
            'price.min' => trans('admin.subscription_plans.validation.price_min'),
            'price.max' => trans('admin.subscription_plans.validation.price_max'),
            'currency.required' => trans('admin.subscription_plans.validation.currency_required'),
            'currency.in' => trans('admin.subscription_plans.validation.currency_in'),
            'billing_period.required' => trans('admin.subscription_plans.validation.billing_period_required'),
            'billing_period.in' => trans('admin.subscription_plans.validation.billing_period_in'),
            'billing_interval.required' => trans('admin.subscription_plans.validation.billing_interval_required'),
            'billing_interval.integer' => trans('admin.subscription_plans.validation.billing_interval_integer'),
            'billing_interval.min' => trans('admin.subscription_plans.validation.billing_interval_min'),
            'billing_interval.max' => trans('admin.subscription_plans.validation.billing_interval_max'),
            'features.array' => trans('admin.subscription_plans.validation.features_array'),
            'features.*.exists' => trans('admin.subscription_plans.validation.features_exists'),
            'is_active.boolean' => trans('admin.subscription_plans.validation.is_active_boolean'),
            'trial_days.required' => trans('admin.subscription_plans.validation.trial_days_required'),
            'trial_days.integer' => trans('admin.subscription_plans.validation.trial_days_integer'),
            'trial_days.min' => trans('admin.subscription_plans.validation.trial_days_min'),
            'trial_days.max' => trans('admin.subscription_plans.validation.trial_days_max'),
        ];
    }
}

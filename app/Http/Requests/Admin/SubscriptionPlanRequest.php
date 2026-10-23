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
            'name' => __('subscriptions.plans.fields.name'),
            'description' => __('subscriptions.plans.fields.description'),
            'price' => __('subscriptions.plans.fields.price'),
            'currency' => __('subscriptions.plans.fields.currency'),
            'billing_period' => __('subscriptions.plans.fields.billing_period'),
            'billing_interval' => __('subscriptions.plans.fields.billing_interval'),
            'features' => __('subscriptions.plans.fields.features'),
            'is_active' => __('subscriptions.plans.fields.is_active'),
            'trial_days' => __('subscriptions.plans.fields.trial_days'),
        ];
    }
}

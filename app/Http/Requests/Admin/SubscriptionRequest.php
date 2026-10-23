<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow logged in users with proper permissions
        return backpack_auth()->check() &&
               backpack_user()->hasPermissionTo('can_create_edit_subscription', 'backpack');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'subscription_plan_id' => 'required|integer|exists:subscription_plans,id',
            'status' => 'required|string|in:pending,active,cancelled,expired,past_due',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'trial_ends_at' => 'nullable|date',
            'next_billing_at' => 'nullable|date',
            'amount' => 'required|numeric|min:0|max:999999.99',
            'currency' => 'required|string|in:CZK,EUR,USD',
            'metadata' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => __('subscriptions.fields.user_id'),
            'subscription_plan_id' => __('subscriptions.fields.subscription_plan_id'),
            'status' => __('subscriptions.fields.status'),
            'starts_at' => __('subscriptions.fields.starts_at'),
            'ends_at' => __('subscriptions.fields.ends_at'),
            'trial_ends_at' => __('subscriptions.fields.trial_ends_at'),
            'next_billing_at' => __('subscriptions.fields.next_billing_at'),
            'amount' => __('subscriptions.fields.amount'),
            'currency' => __('subscriptions.fields.currency'),
            'metadata' => __('subscriptions.fields.metadata'),
        ];
    }
}

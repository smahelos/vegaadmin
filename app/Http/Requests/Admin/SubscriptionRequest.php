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
            'user_id' => trans('admin.subscriptions.user'),
            'subscription_plan_id' => trans('admin.subscriptions.subscription_plan'),
            'status' => trans('admin.subscriptions.status'),
            'starts_at' => trans('admin.subscriptions.starts_at'),
            'ends_at' => trans('admin.subscriptions.ends_at'),
            'trial_ends_at' => trans('admin.subscriptions.trial_ends_at'),
            'next_billing_at' => trans('admin.subscriptions.next_billing_at'),
            'amount' => trans('admin.subscriptions.amount'),
            'currency' => trans('admin.subscriptions.currency'),
            'metadata' => trans('admin.subscriptions.metadata'),
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => trans('admin.subscriptions.validation.user_required'),
            'user_id.integer' => trans('admin.subscriptions.validation.user_integer'),
            'user_id.exists' => trans('admin.subscriptions.validation.user_exists'),
            'subscription_plan_id.required' => trans('admin.subscriptions.validation.subscription_plan_required'),
            'subscription_plan_id.integer' => trans('admin.subscriptions.validation.subscription_plan_integer'),
            'subscription_plan_id.exists' => trans('admin.subscriptions.validation.subscription_plan_exists'),
            'status.required' => trans('admin.subscriptions.validation.status_required'),
            'status.in' => trans('admin.subscriptions.validation.status_in'),
            'starts_at.date' => trans('admin.subscriptions.validation.starts_at_date'),
            'ends_at.date' => trans('admin.subscriptions.validation.ends_at_date'),
            'ends_at.after' => trans('admin.subscriptions.validation.ends_at_after'),
            'trial_ends_at.date' => trans('admin.subscriptions.validation.trial_ends_at_date'),
            'next_billing_at.date' => trans('admin.subscriptions.validation.next_billing_at_date'),
            'amount.required' => trans('admin.subscriptions.validation.amount_required'),
            'amount.numeric' => trans('admin.subscriptions.validation.amount_numeric'),
            'amount.min' => trans('admin.subscriptions.validation.amount_min'),
            'amount.max' => trans('admin.subscriptions.validation.amount_max'),
            'currency.required' => trans('admin.subscriptions.validation.currency_required'),
            'currency.in' => trans('admin.subscriptions.validation.currency_in'),
            'metadata.string' => trans('admin.subscriptions.validation.metadata_string'),
            'metadata.max' => trans('admin.subscriptions.validation.metadata_max'),
        ];
    }
}

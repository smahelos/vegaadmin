<?php

namespace App\Http\Requests\Admin;

use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Application\User\Services\EntityLimitChecker;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base Entity Request
 * 
 * Abstract base class for admin requests that need entity limit checking
 * Provides universal limit enforcement for all admin operations
 */
abstract class BaseEntityRequest extends FormRequest
{
    /**
     * Enhanced authorization with limit checking
     */
    public function authorize(): bool
    {
        if (!backpack_auth()->check()) {
            return false;
        }
        
        $user = backpack_auth()->user();
        
        // Check basic permissions first
        if (!$user->can($this->getRequiredPermission())) {
            return false;
        }
        
        // Skip limit check for updates or if user has unlimited access
        if (!$this->isCreatingEntity() || $this->hasUnlimitedAccess()) {
            return true;
        }
        
        // Check entity limits for creation
        $checker = app(EntityLimitChecker::class);
        $limitCheck = $checker->check(
            (int) $user->id,
            $this->getEntityType(),
            $this->getLimitType(),
            $this->getPeriodType(),
            $this->getLimitValue()
        );
        
        if (!$limitCheck['allowed']) {
            throw new EntityLimitExceededException($limitCheck);
        }
        
        return true;
    }

    /**
     * Get required permission for this entity - must be implemented by child classes
     */
    abstract protected function getRequiredPermission(): string;

    /**
     * Get validation rules - must be implemented by child classes
     */
    abstract public function rules(): array;

    /**
     * Get custom attributes for validation errors
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Prepare the data for validation - can be overridden
     */
    protected function prepareForValidation(): void
    {
        // Can be overridden in child classes for custom data preparation
    }

    /**
     * Handle a passed validation attempt - can be overridden
     */
    protected function passedValidation(): void
    {
        // Can be overridden in child classes for post-validation logic
    }

    /**
     * Get the validation rules that apply to the request based on HTTP method
     */
    protected function getMethodBasedRules(): array
    {
        $rules = $this->rules();
        
        // For updates, make certain fields optional
        if ($this->isUpdatingEntity()) {
            return $this->makeRulesOptionalForUpdate($rules);
        }
        
        return $rules;
    }

    /**
     * Make rules optional for update requests
     */
    protected function makeRulesOptionalForUpdate(array $rules): array
    {
        foreach ($rules as $field => $rule) {
            if (is_string($rule) && str_contains($rule, 'required')) {
                $rules[$field] = str_replace('required', 'sometimes', $rule);
            } elseif (is_array($rule) && in_array('required', $rule)) {
                $key = array_search('required', $rule);
                $rules[$field][$key] = 'sometimes';
            }
        }
        
        return $rules;
    }

    /**
     * Get entity type specific to this request
     * Override this method in child classes if auto-detection doesn't work
     */
    protected function getEntityType(): string
    {
        // Remove 'Request' suffix and convert to lowercase
        $className = class_basename($this);
        $cleanName = str_replace('Request', '', $className);
        
        return strtolower($cleanName);
    }

    /**
     * Check if limit checking should be bypassed for this request
     * Override in child classes for custom logic
     */
    protected function shouldBypassLimitCheck(): bool
    {
        return false;
    }

    /**
     * Get the limit type for this entity (count, value, size)
     * Override in child classes for non-count based limits
     */
    protected function getLimitType(): string
    {
        return 'count';
    }

    /**
     * Get the period type for this entity (daily, weekly, monthly, yearly, lifetime)
     * Override in child classes for non-monthly limits
     */
    protected function getPeriodType(): string
    {
        return 'monthly';
    }

    /**
     * Get the value to increment when recording usage
     * Override in child classes for value-based or size-based limits
     */
    protected function getLimitValue(): int|float
    {
        return 1;
    }

    /**
     * Check if current request is for entity creation
     */
    protected function isCreatingEntity(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if current request is for entity update
     */
    protected function isUpdatingEntity(): bool
    {
        return $this->isMethod('PUT') || $this->isMethod('PATCH');
    }

    /**
     * Check if user has unlimited access for this entity type
     */
    protected function hasUnlimitedAccess(?string $entityType = null): bool
    {
        $entityType = $entityType ?? $this->getEntityType();
        $permission = "can_create_{$entityType}_unlimited";
        return backpack_auth()->check() && backpack_auth()->user()->can($permission);
    }
}

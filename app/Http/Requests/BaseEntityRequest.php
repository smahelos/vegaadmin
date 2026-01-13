<?php

namespace App\Http\Requests;

use App\Domain\User\Exceptions\EntityLimitExceededException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Application\User\Contracts\UELSApplicationServiceInterface;

/**
 * Base Entity Request for Frontend
 * 
 * Abstract base class for frontend requests that need entity limit checking
 * Provides universal limit enforcement for all frontend operations
 */
abstract class BaseEntityRequest extends FormRequest
{

    /**
     * Enhanced authorization with limit checking for frontend users
     */
    public function authorize(): bool
    {
        if (!Auth::check()) {
            return false;
        }
        
        $user = Auth::user();
        
        // Check basic permissions first (using web guard) - skip if no permission required
        $requiredPermission = $this->getRequiredPermission();
        if (!empty($requiredPermission) && !$user->can($requiredPermission)) {
            return false;
        }
        
        // Skip limit check for updates or if user has unlimited access
        if (!$this->isCreatingEntity() || $this->hasUnlimitedAccess()) {
            return true;
        }
        
        // Check entity limits for creation - use best available period
        $limitService = app(UELSApplicationServiceInterface::class);
        $bestPeriod = $limitService->getBestPeriodType($user->id, $this->getEntityType(), 'count');
        $limitStats = $limitService->getUsageStatistics($user->id, $this->getEntityType(), 'count', $bestPeriod);
        
        if (!$limitStats['can_create']) {
            throw new EntityLimitExceededException($limitStats);
        }
        
        return true;
    }

    /**
     * Handle authorization failure by converting limit exceptions to redirect with flash message
     */
    protected function failedAuthorization()
    {
        $previous = $this->exception ?? null;
        if ($previous instanceof EntityLimitExceededException) {
            session()->flash('error', trans('suppliers.messages.limit_exceeded'));
            abort(redirect()->back());
        }
        parent::failedAuthorization();
    }

    /**
     * Get required permission for this entity - must be implemented by child classes
     * Should return frontend permission names like 'frontend.can_create_edit_invoice'
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
            // This can be customized in child classes
            $rules = $this->makeFieldsOptionalForUpdate($rules);
        }
        
        return $rules;
    }

    /**
     * Determine if this is an entity creation request
     */
    protected function isCreatingEntity(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Determine if this is an entity update request
     */
    protected function isUpdatingEntity(): bool
    {
        return $this->isMethod('PUT') || $this->isMethod('PATCH');
    }

    /**
     * Make specified fields optional for update operations
     */
    protected function makeFieldsOptionalForUpdate(array $rules): array
    {
        // Default implementation - can be overridden in child classes
        foreach ($rules as $field => &$rule) {
            if (is_string($rule) && str_contains($rule, 'required')) {
                $rule = str_replace('required', 'sometimes', $rule);
            } elseif (is_array($rule)) {
                $rule = array_map(function ($r) {
                    return $r === 'required' ? 'sometimes' : $r;
                }, $rule);
            }
        }
        
        return $rules;
    }

    /**
     * Check if user has unlimited access for this entity type
     */
    protected function hasUnlimitedAccess(): bool
    {
        $user = Auth::user();
        $unlimitedPermission = 'frontend.unlimited_access.' . $this->getEntityType();
        
        return $user && $user->can($unlimitedPermission);
    }

    /**
     * Override the current user method to use standard Laravel Auth
     */
    protected function getCurrentUser()
    {
        return Auth::user();
    }

    /**
     * Override the user guard to use web guard instead of backpack
     */
    protected function getUserGuard(): string
    {
        return 'web';
    }
}

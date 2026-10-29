<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Party\DTO\PartyProfile;
use App\Http\Requests\Party\PartyInput;
use App\Http\Requests\Party\PartyLogoInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A business saved in the admin, for the user chosen as its owner on creation.
 */
final class BusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('POST') ? 'can_create_business' : 'can_update_business';

        return backpack_user()?->can($permission) === true;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        $rules = PartyInput::rules($this->all(), static fn (string $field): string => $field) + PartyLogoInput::rules();

        if ($this->isMethod('POST')) {
            $rules['user_id'] = [
                'required',
                'integer',
                'exists:users,id',
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return PartyInput::attributes(static fn (string $field): string => $field) + [
            'user_id' => __('parties.fields.owner'),
            'logo' => __('parties.fields.logo'),
        ];
    }

    public function profile(): PartyProfile
    {
        return PartyInput::profile($this->validated());
    }
}

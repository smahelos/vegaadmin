<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Party\DTO\InvoicingSettings;
use App\Domain\Party\DTO\PartyProfile;
use App\Http\Requests\Party\InvoicingSettingsInput;
use App\Http\Requests\Party\PartyInput;
use App\Http\Requests\Party\PartyLogoInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A business saved in the admin, for the user chosen as its owner on creation.
 */
final class BusinessRequest extends FormRequest
{
    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        $sameKey = static fn (string $field): string => $field;
        $rules = PartyInput::rules($this->all(), $sameKey) + InvoicingSettingsInput::rules($sameKey) + PartyLogoInput::rules();

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
        $sameKey = static fn (string $field): string => $field;

        return PartyInput::attributes($sameKey) + InvoicingSettingsInput::attributes($sameKey) + [
            'user_id' => __('parties.fields.owner'),
            'logo' => __('parties.fields.logo'),
        ];
    }

    public function profile(): PartyProfile
    {
        return PartyInput::profile($this->validated());
    }

    public function invoicing(): InvoicingSettings
    {
        return InvoicingSettingsInput::settings($this->validated());
    }
}

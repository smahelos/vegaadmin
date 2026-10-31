<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Application\Invoice\DTO\DocumentPartyOptionDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DocumentPartyOptionResource extends JsonResource
{
    public function __construct(private readonly DocumentPartyOptionDTO $option)
    {
        parent::__construct($option);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if ($this->option->isDefault !== null) {
            return [
                'id' => $this->option->id,
                'label' => $this->option->label,
                'is_default' => $this->option->isDefault,
                'account' => [
                    'country' => $this->option->account?->account->country->value,
                    'number' => $this->option->account?->account->number,
                    'bank_code' => $this->option->account?->account->bankCode,
                    'bank_name' => $this->option->account?->bankName,
                    'iban' => $this->option->account?->account->iban,
                    'swift' => $this->option->account?->account->swift,
                ],
            ];
        }

        return [
            'id' => $this->option->id,
            'name' => $this->option->label,
            'profile' => [
                'logo_url' => $this->option->logo === null ? null : asset('storage/' . $this->option->logo),
                'name' => $this->option->profile?->name,
                'registration_country' => $this->option->profile?->registrationNumber?->country->value,
                'company_number' => $this->option->profile?->registrationNumber?->number,
                'vat_number' => $this->option->profile?->vatNumber?->value,
                'street' => $this->option->profile?->address?->street,
                'city' => $this->option->profile?->address?->city,
                'postal_code' => $this->option->profile?->address?->postalCode,
                'country' => $this->option->profile?->address?->country->value,
                'email' => $this->option->profile?->email,
                'phone' => $this->option->profile?->phone,
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Party\ValueObjects\VatNumber;
use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Shared\Geography\Enums\Country;
use App\Models\Expense;
use App\Models\Invoice;

/**
 * The party and account columns invoices and expenses share: `supplier_*`, `customer_*` and `account_*` with the
 * business, contact and account they were copied from.
 */
final class EloquentDocumentPartiesMapper
{
    public static function toDocumentParties(Invoice|Expense $document): DocumentParties
    {
        $contactId = $document->getAttribute('contact_id');
        $bankAccountId = $document->getAttribute('bank_account_id');

        return new DocumentParties(
            businessId: BusinessId::fromInt((int) $document->getAttribute('business_id')),
            contactId: $contactId === null ? null : ContactId::fromInt((int) $contactId),
            bankAccountId: $bankAccountId === null ? null : BankAccountId::fromInt((int) $bankAccountId),
            supplier: self::toParty($document, 'supplier'),
            customer: self::toParty($document, 'customer'),
            account: self::toAccount($document),
            supplierLogo: $document->getAttribute('supplier_logo'),
        );
    }

    /**
     * @return array<string, int|string|null>
     */
    public static function toAttributes(DocumentParties $parties): array
    {
        $attributes = [
            'supplier_logo' => $parties->supplierLogo,
            'business_id' => $parties->businessId->value,
            'contact_id' => $parties->contactId?->value,
            'bank_account_id' => $parties->bankAccountId?->value,
            'account_iban' => $parties->account?->account->iban,
            'account_number' => $parties->account?->account->number,
            'account_bank_code' => $parties->account?->account->bankCode,
            'account_country' => $parties->account?->account->country->value,
            'account_swift' => $parties->account?->account->swift,
            'account_bank_name' => $parties->account?->bankName,
        ];

        foreach ([
            'supplier' => $parties->supplier,
            'customer' => $parties->customer,
        ] as $role => $party) {
            $attributes += self::toPartyAttributes($role, $party);
        }

        return $attributes;
    }

    /**
     * @return array<string, string|null>
     */
    private static function toPartyAttributes(string $role, DocumentPartyDTO $party): array
    {
        return [
            $role . '_name' => $party->name,
            $role . '_company_number' => $party->registrationNumber?->number,
            $role . '_registration_country' => $party->registrationNumber?->country->value,
            $role . '_vat_number' => $party->vatNumber?->value,
            $role . '_street' => $party->address?->street,
            $role . '_city' => $party->address?->city,
            $role . '_postal_code' => $party->address?->postalCode,
            $role . '_country' => $party->address?->country->value,
            $role . '_email' => $party->email,
            $role . '_phone' => $party->phone,
        ];
    }

    private static function toParty(Invoice|Expense $document, string $role): DocumentPartyDTO
    {
        return new DocumentPartyDTO(
            name: (string) $document->getAttribute($role . '_name'),
            registrationNumber: RegistrationNumber::fromParts(
                self::country($document->getAttribute($role . '_registration_country')),
                $document->getAttribute($role . '_company_number'),
            ),
            vatNumber: VatNumber::fromNullable($document->getAttribute($role . '_vat_number')),
            address: Address::fromParts(
                $document->getAttribute($role . '_street'),
                $document->getAttribute($role . '_city'),
                $document->getAttribute($role . '_postal_code'),
                self::country($document->getAttribute($role . '_country')),
            ),
            email: $document->getAttribute($role . '_email'),
            phone: $document->getAttribute($role . '_phone'),
        );
    }

    private static function toAccount(Invoice|Expense $document): ?DocumentAccountDTO
    {
        $iban = $document->getAttribute('account_iban');
        $number = $document->getAttribute('account_number');
        if ($iban === null && $number === null) {
            return null;
        }

        return new DocumentAccountDTO(
            BankAccount::fromParts(
                $iban,
                $number,
                $document->getAttribute('account_bank_code'),
                self::country($document->getAttribute('account_country')),
                $document->getAttribute('account_swift'),
            ),
            $document->getAttribute('account_bank_name'),
        );
    }

    private static function country(?string $code): ?Country
    {
        return $code === null ? null : Country::from($code);
    }
}

<?php

namespace App\Domain\Party\DTO;

/**
 * Immutable write data for Supplier mutations.
 * All fields are optional to support partial updates; null means "do not change".
 */
class SupplierWriteData
{
    public readonly ?string $ico;

    /**
     * @param  mixed  $supplier_logo  Can be string path or UploadedFile
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $street = null,
        public readonly ?string $city = null,
        public readonly ?string $zip = null,
        public readonly ?string $country = null,
        ?string $ico = null,
        public readonly ?string $dic = null,
        public readonly ?string $shortcut = null,
        public readonly ?string $description = null,
        public readonly mixed $supplier_logo = null, // Can be string path or UploadedFile
        public readonly ?string $account_number = null,
        public readonly ?string $bank_code = null,
        public readonly ?string $bank_name = null,
        public readonly ?string $iban = null,
        public readonly ?string $swift = null,
        public readonly ?bool $has_payment_info = null,
        public readonly ?int $user_id = null,
        public readonly bool $is_default = false,
    ) {
        $ico = $ico === null ? null : trim($ico);
        // ico is unique per user, so an empty one is stored as null, never as an empty string
        $this->ico = $ico === '' ? null : $ico;
    }

    /**
     * Convert to associative array for infrastructure layer usage.
     * Filters out null values to avoid overwriting existing data with null during updates.
     */
    public function toArray(): array
    {
        $properties = get_object_vars($this);

        return array_filter($properties, fn ($value) => $value !== null);
    }
}

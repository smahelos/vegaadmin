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
     * Create from associative array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            street: $data['street'] ?? null,
            city: $data['city'] ?? null,
            zip: $data['zip'] ?? null,
            country: $data['country'] ?? null,
            ico: $data['ico'] ?? null,
            dic: $data['dic'] ?? null,
            shortcut: $data['shortcut'] ?? null,
            description: $data['description'] ?? null,
            supplier_logo: $data['supplier_logo'] ?? null,
            account_number: $data['account_number'] ?? null,
            bank_code: $data['bank_code'] ?? null,
            bank_name: $data['bank_name'] ?? null,
            iban: $data['iban'] ?? null,
            swift: $data['swift'] ?? null,
            has_payment_info: $data['has_payment_info'] ?? null,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            is_default: (bool) ($data['is_default'] ?? false),
        );
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

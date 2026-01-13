<?php

namespace App\Domain\Party\DTO;

/**
 * Immutable write data for Client mutations.
 * All fields are optional to support partial updates; null means "do not change".
 */
class ClientWriteData
{
    /**
     * @param string|null $name
     * @param string|null $email
     * @param string|null $phone
     * @param string|null $street
     * @param string|null $city
     * @param string|null $zip
     * @param string|null $country
     * @param string|null $ico
     * @param string|null $dic
     * @param string|null $shortcut
     * @param string|null $description
     * @param int|null $user_id
     * @param bool $is_default
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $street = null,
        public readonly ?string $city = null,
        public readonly ?string $zip = null,
        public readonly ?string $country = null,
        public readonly ?string $ico = null,
        public readonly ?string $dic = null,
        public readonly ?string $shortcut = null,
        public readonly ?string $description = null,
        public readonly ?int $user_id = null,
        public readonly bool $is_default = false,
    ) {}

    /**
     * Convert to associative array for infrastructure layer usage.
     * Filters out null values to avoid overwriting existing data with null during updates.
     */
    public function toArray(): array
    {
        $properties = get_object_vars($this);
        return array_filter($properties, fn($value) => $value !== null);
    }
}

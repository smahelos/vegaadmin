<?php

declare(strict_types=1);

namespace App\Http\Requests\Party;

final class AdminDocumentPartyInput
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function rules(
        array $input,
        string $role,
        bool $copyContact,
    ): array {
        $rules = DocumentPartyInput::rules(
            $input,
            $role,
            true,
        );
        if ($copyContact) {
            return array_fill_keys(array_keys($rules), ['exclude']);
        }

        return $rules;
    }
}

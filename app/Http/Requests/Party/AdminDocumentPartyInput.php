<?php

declare(strict_types=1);

namespace App\Http\Requests\Party;

use Illuminate\Http\Request;

final class AdminDocumentPartyInput
{
    /** Request attribute with the stored document's contact id, set by the admin update from the stored entry */
    public const STORED_CONTACT = 'stored_contact_id';

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
            return array_fill_keys(
                array_keys($rules),
                ['exclude'],
            );
        }

        return $rules;
    }

    /**
     * The document keeps its stored contact, so the entered party is validated instead of copied from the contact.
     */
    public static function keepsStoredContact(Request $request): bool
    {
        $stored = $request->attributes->get(self::STORED_CONTACT);

        return $stored !== null && (int) $stored === $request->integer('contact_id');
    }
}

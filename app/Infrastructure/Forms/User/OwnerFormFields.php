<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\User;

use App\Models\User;

/**
 * The owner filter of admin lists whose records belong to a user.
 */
final class OwnerFormFields
{
    /** @return array<string, string> */
    public static function filter(string $label): array
    {
        return [
            'name' => 'user_id',
            'type' => 'select2',
            'label' => $label,
        ];
    }

    /** @return array<int, string> user name by id, sorted by name */
    public static function names(): array
    {
        $userQuery = User::query();
        $userQuery->orderBy('name');
        $names = $userQuery->pluck('name', 'id');

        return $names->all();
    }
}

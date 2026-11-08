<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\User;

use App\Domain\User\Enums\LimitedEntity;

final class EntityUsageFormFields
{
    /**
     * @return array<string, mixed>
     */
    public static function field(LimitedEntity $entity): array
    {
        return [
            'name' => 'entity_usage',
            'label' => __('role_limits.usage.title'),
            'type' => 'entity_usage',
            'limited_entity' => $entity->value,
        ];
    }
}

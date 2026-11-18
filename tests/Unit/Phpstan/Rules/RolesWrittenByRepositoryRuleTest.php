<?php

declare(strict_types=1);

namespace Tests\Unit\Phpstan\Rules;

use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use App\Infrastructure\Persistence\Eloquent\User\Repositories\EloquentUserRepository;
use Billex\PHPStan\Rules\RolesWrittenByRepositoryRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @extends RuleTestCase<RolesWrittenByRepositoryRule>
 */
final class RolesWrittenByRepositoryRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RolesWrittenByRepositoryRule;
    }

    #[Test]
    public function a_role_written_outside_its_writer_is_reported(): void
    {
        $this->analyse(
            [__DIR__ . '/data/roles-written-outside-the-repository.php'],
            [
                ['Only ' . EloquentUserRepository::class . ' calls assignRole() on roles and permissions.', 15],
                ['Only ' . EloquentUserRepository::class . ' calls roles() on roles and permissions.', 16],
                ['Only ' . PermissionCatalogSync::class . ' calls givePermissionTo() on roles and permissions.', 23],
            ],
        );
    }

    #[Test]
    public function the_writers_write_roles(): void
    {
        $this->analyse(
            [
                __DIR__ . '/../../../../app/Infrastructure/Persistence/Eloquent/User/Repositories/EloquentUserRepository.php',
                __DIR__ . '/../../../../app/Infrastructure/Authorization/Services/PermissionCatalogSync.php',
            ],
            [],
        );
    }
}

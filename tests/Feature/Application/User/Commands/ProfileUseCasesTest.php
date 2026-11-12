<?php

declare(strict_types=1);

namespace Tests\Feature\Application\User\Commands;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\User\Commands\ChangePassword;
use App\Application\User\Commands\UpdateProfile;
use App\Application\User\Queries\GetProfile;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Domain\User\ValueObjects\UserPassword;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsBackendUsers;
use Tests\Support\BuildsParties;
use Tests\Support\RunsUseCases;
use Tests\TestCase;

final class ProfileUseCasesTest extends TestCase
{
    use BuildsBackendUsers;
    use BuildsParties;
    use RunsUseCases;

    #[Test]
    public function a_user_reads_and_updates_their_own_profile(): void
    {
        $user = $this->frontendUser();
        $email = 'renamed-' . uniqid() . '@billex.test';

        $this->runAs(
            $this->idOf($user),
            new UpdateProfile(
                $this->idOf($user),
                'Renamed',
                $email,
            ),
        );
        $profile = $this->runAs(
            $this->idOf($user),
            new GetProfile(
                $this->idOf($user),
            ),
        );

        $this->assertSame('Renamed', $profile->name);
        $this->assertSame($email, $profile->email);
    }

    #[Test]
    public function the_new_password_signs_in(): void
    {
        $user = $this->frontendUser();

        $this->runAs(
            $this->idOf($user),
            new ChangePassword(
                $this->idOf($user),
                UserPassword::fromPlainText('Another-Secret-9'),
            ),
        );

        $user->refresh();
        $this->assertTrue(
            Hash::check('Another-Secret-9', $user->password),
        );
    }

    #[Test]
    public function a_user_cannot_change_another_users_profile(): void
    {
        $user = $this->frontendUser();
        $other = $this->frontendUser();

        $this->expectException(PermissionDeniedException::class);

        $this->runAs(
            $this->idOf($user),
            new UpdateProfile(
                $this->idOf($other),
                'Taken over',
                'taken-' . uniqid() . '@billex.test',
            ),
        );
    }

    #[Test]
    public function the_profile_needs_profile_update(): void
    {
        $user = $this->frontendUser();
        $user->syncRoles([]);

        $this->expectException(PermissionDeniedException::class);

        $this->runAs(
            $this->idOf($user),
            new GetProfile(
                $this->idOf($user),
            ),
        );
    }

    #[Test]
    public function an_admin_cannot_change_a_profile_through_the_profile_command(): void
    {
        // profile.update exists only in web: an admin changes accounts through UpdateUser
        $admin = $this->backendUser(Permission::USER_UPDATE);
        $user = $this->frontendUser();
        $useCases = $this->app->make(AuthorizingUseCaseRunnerInterface::class);

        $this->expectException(PermissionDeniedException::class);

        $useCases->run(
            Actor::admin(
                $this->idOf($admin),
            ),
            new UpdateProfile(
                $this->idOf($user),
                'Renamed',
                'renamed-' . uniqid() . '@billex.test',
            ),
        );
    }
}

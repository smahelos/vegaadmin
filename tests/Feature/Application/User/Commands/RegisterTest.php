<?php

declare(strict_types=1);

namespace Tests\Feature\Application\User\Commands;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\User\Commands\Register;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\Support\Fakes\Shared\FixedClock;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use BuildsParties;

    #[Test]
    public function an_anonymous_visitor_registers_the_account_with_its_default_business_and_account(): void
    {
        $email = 'owner-' . uniqid() . '@billex.test';
        $this->app->instance(
            ClockInterface::class,
            new FixedClock(
                new DateTimeImmutable('2026-11-06 10:00:00'),
            ),
        );

        $registered = $this->register(
            $email,
            new BusinessWriteData(
                $this->profile(
                    'Studio Nováková',
                    '27074358',
                    email: 'studio@billex.test',
                    address: $this->brnoAddress(),
                ),
                null,
            ),
            $this->czechAccount(),
        );

        $userQuery = User::query();
        $user = $userQuery->findOrFail($registered->id);
        $businessQuery = Business::query();
        $businessQuery->where('user_id', $user->id);
        $business = $businessQuery->sole();
        $this->assertTrue(
            $user->hasRole('frontend_user'),
        );
        $this->assertSame(
            '2026-11-06 10:00:00',
            $user->plan_role_assigned_at?->format('Y-m-d H:i:s'),
        );
        $this->assertSame($business->id, $user->default_business_id);
        $this->assertSame('studio@billex.test', $business->email);
        $this->assertSame($email, $user->email);
        $bankAccountQuery = BankAccount::query();
        $bankAccountQuery->where('business_id', $business->id);
        $this->assertSame(
            $bankAccountQuery->sole()->id,
            $business->default_bank_account_id,
        );
    }

    #[Test]
    public function a_business_over_the_limit_leaves_no_account_behind(): void
    {
        $this->limitFrontendUser(LimitedEntity::BUSINESS, 0);
        $email = 'owner-' . uniqid() . '@billex.test';

        try {
            $this->register(
                $email,
                new BusinessWriteData($this->profile(), null),
                null,
            );
            $this->fail('The registration succeeded over the business limit');
        } catch (EntityLimitExceededException) {
        }

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    #[Test]
    public function the_first_business_needs_the_new_users_business_create(): void
    {
        $roleQuery = Role::query();
        $roleQuery->where('name', 'frontend_user');
        $role = $roleQuery->sole();
        $role->revokePermissionTo(Permission::BUSINESS_CREATE);
        $email = 'owner-' . uniqid() . '@billex.test';

        try {
            $this->register(
                $email,
                new BusinessWriteData($this->profile(), null),
                null,
            );
            $this->fail(
                'The registration created a business without business.create',
            );
        } catch (PermissionDeniedException) {
        }

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    private function register(
        string $email,
        BusinessWriteData $business,
        ?BankAccountWriteData $account,
    ): UserDTO {
        $useCases = $this->app->make(AuthorizingUseCaseRunnerInterface::class);

        return $useCases->run(
            Actor::anonymous(),
            new Register(
                new UserWriteData(
                    'Jana Nováková',
                    $email,
                    'secret-password',
                ),
                $business,
                $account,
            ),
        );
    }
}

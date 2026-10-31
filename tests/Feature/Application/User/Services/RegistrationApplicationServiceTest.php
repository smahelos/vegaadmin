<?php

declare(strict_types=1);

namespace Tests\Feature\Application\User\Services;

use App\Application\User\Services\RegistrationApplicationService;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class RegistrationApplicationServiceTest extends TestCase
{
    use BuildsParties;

    #[Test]
    public function registration_creates_the_account_with_its_default_business_and_account(): void
    {
        $email = 'owner-' . uniqid() . '@billex.test';

        $registered = $this->app->make(RegistrationApplicationService::class)->register(
            new UserWriteData(
                'Jana Nováková',
                $email,
                'secret-password',
            ),
            new BusinessWriteData($this->profile('Studio Nováková', '27074358', email: 'studio@billex.test', address: $this->brnoAddress()), null),
            $this->czechAccount(),
        );

        $user = User::query()->findOrFail($registered->id);
        $business = Business::query()->where('user_id', $user->id)->sole();
        $this->assertTrue($user->hasRole('frontend_user'));
        $this->assertSame($business->id, $user->default_business_id);
        $this->assertSame('studio@billex.test', $business->email);
        $this->assertSame($email, $user->email);
        $this->assertSame(
            BankAccount::query()->where('business_id', $business->id)->sole()->id,
            $business->default_bank_account_id,
        );
    }

    #[Test]
    public function a_failing_business_leaves_no_account_behind(): void
    {
        $this->limitActive('business', 0);
        $email = 'owner-' . uniqid() . '@billex.test';

        try {
            $this->app->make(RegistrationApplicationService::class)->register(
                new UserWriteData(
                    'Jana Nováková',
                    $email,
                    'secret-password',
                ),
                new BusinessWriteData($this->profile(), null),
                null,
            );
            $this->fail('The registration succeeded over the business limit');
        } catch (EntityLimitExceededException) {
        }

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }
}

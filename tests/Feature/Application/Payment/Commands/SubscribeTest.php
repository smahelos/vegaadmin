<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Payment\Commands;

use App\Application\Payment\Commands\Subscribe;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Domain\Payment\ValueObjects\SubscriptionPlanId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsBackendUsers;
use Tests\Support\BuildsParties;
use Tests\Support\Fakes\Payment\FakeSubscriptionPaymentService;
use Tests\TestCase;

final class SubscribeTest extends TestCase
{
    use BuildsBackendUsers;
    use BuildsParties;

    #[Test]
    public function an_admin_is_refused_for_an_owner_who_may_not_subscribe(): void
    {
        $payments = new FakeSubscriptionPaymentService;
        $this->app->instance(SubscriptionPaymentServiceInterface::class, $payments);
        $admin = $this->backendUser(Permission::SUBSCRIPTION_CREATE);
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $plan = $subscriptionPlanFactory->create();
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        try {
            $useCases->run(
                Actor::admin(
                    $this->idOf($admin),
                ),
                new Subscribe(
                    $this->idOf($owner),
                    SubscriptionPlanId::fromInt($plan->id),
                    'gopay',
                    'https://billex.test/return',
                    'https://billex.test/notify',
                    'EN',
                ),
            );
            $this->fail('The owner\'s missing permission was not checked');
        } catch (PermissionDeniedException) {
            $subscriptionQuery = Subscription::where('user_id', $owner->id);
            $this->assertFalse($subscriptionQuery->exists());
            $this->assertSame([], $payments->initiated);
        }
    }
}

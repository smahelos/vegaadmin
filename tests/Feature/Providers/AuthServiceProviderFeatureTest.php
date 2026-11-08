<?php

namespace Tests\Feature\Providers;

use App\Infrastructure\Authorization\Policies\Payment\SubscriptionPolicy;
use App\Models\Subscription;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServiceProviderFeatureTest extends TestCase
{
    #[Test]
    public function policies_are_registered_with_gate(): void
    {
        $this->assertInstanceOf(
            SubscriptionPolicy::class,
            Gate::getPolicyFor(Subscription::class),
        );
    }
}

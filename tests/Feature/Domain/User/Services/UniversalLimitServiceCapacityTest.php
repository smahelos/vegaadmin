<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\User\Services;

use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Models\Business;
use App\Models\Contact;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class UniversalLimitServiceCapacityTest extends TestCase
{
    use BuildsParties;

    #[Test]
    public function the_usage_of_a_capacity_limit_is_the_number_of_active_records(): void
    {
        $user = $this->frontendUser();
        Contact::factory()->count(2)->create(['user_id' => $user->id]);
        Contact::factory()->archived()->create(['user_id' => $user->id]);
        Business::factory()->create(['user_id' => $user->id]);
        Contact::factory()->create();

        $limits = app(UniversalLimitServiceInterface::class);
        $contacts = $limits->checkLimit(
            $user->id,
            'contact',
            'active',
            'lifetime',
        );
        $businesses = $limits->checkLimit(
            $user->id,
            'business',
            'active',
            'lifetime',
        );

        $this->assertSame(2, $contacts['current_usage']);
        $this->assertSame(16, $contacts['limit']);
        $this->assertSame(1, $businesses['current_usage']);
    }
}

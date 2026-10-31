<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Party;

use App\Livewire\Party\BusinessDetail;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class BusinessDetailTest extends TestCase
{
    use BuildsParties;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
        $this->user = $this->frontendUser();
        $this->actingAs($this->user);
    }

    #[Test]
    public function archiving_the_default_business_asks_for_a_new_default_among_the_others(): void
    {
        $default = $this->business();
        $other = $this->business();
        $this->makeDefault($default);

        $detail = Livewire::test(BusinessDetail::class, ['businessId' => $default->id])
            ->call('archive')
            ->assertSet('choosingNewDefault', true);
        $this->assertNull($default->fresh()?->archived_at);

        $detail->set('newDefault', (string) $other->id)
            ->call('archive')
            ->assertSet('choosingNewDefault', false);
        $this->assertNotNull($default->fresh()?->archived_at);
        $this->assertSame($other->id, $this->user->fresh()?->default_business_id);
    }

    #[Test]
    public function the_last_active_business_can_be_archived_and_leaves_no_default(): void
    {
        $only = $this->business();
        $this->makeDefault($only);

        Livewire::test(BusinessDetail::class, ['businessId' => $only->id])->call('archive');

        $this->assertNotNull($only->fresh()?->archived_at);
        $this->assertNull($this->user->fresh()?->default_business_id);
    }

    #[Test]
    public function a_business_restored_while_none_is_active_becomes_the_default(): void
    {
        $archived = Business::factory()->archived()->create(['user_id' => $this->user->id]);

        Livewire::test(BusinessDetail::class, ['businessId' => $archived->id])
            ->call('restore')
            ->assertSet('successMessage', __('businesses.messages.restored'));

        $this->assertNull($archived->fresh()?->archived_at);
        $this->assertSame($archived->id, $this->user->fresh()?->default_business_id);
    }

    #[Test]
    public function restoring_needs_a_free_place(): void
    {
        $this->limitActive('business', 1);
        $this->business();
        $archived = Business::factory()->archived()->create(['user_id' => $this->user->id]);

        Livewire::test(BusinessDetail::class, ['businessId' => $archived->id])
            ->call('restore')
            ->assertSet('errorMessage', __('businesses.messages.limit_exceeded'));

        $this->assertNotNull($archived->fresh()?->archived_at);
    }

    #[Test]
    public function makes_another_business_the_default(): void
    {
        $default = $this->business();
        $other = $this->business();
        $this->makeDefault($default);

        Livewire::test(BusinessDetail::class, ['businessId' => $other->id])->call('makeDefault');

        $this->assertSame($other->id, $this->user->fresh()?->default_business_id);
    }

    #[Test]
    public function archiving_needs_the_permission(): void
    {
        $business = $this->business();
        $this->user->removeRole('frontend_user');
        $this->user->givePermissionTo('frontend.can_view_business');

        Livewire::test(BusinessDetail::class, ['businessId' => $business->id])
            ->call('archive')
            ->assertForbidden();

        $this->assertNull($business->fresh()?->archived_at);
    }

    private function business(): Business
    {
        return Business::factory()->create(['user_id' => $this->user->id]);
    }

    private function makeDefault(Business $business): void
    {
        $this->user->forceFill(['default_business_id' => $business->id])->save();
    }
}

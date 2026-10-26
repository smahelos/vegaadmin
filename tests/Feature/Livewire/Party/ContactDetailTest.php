<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Party;

use App\Livewire\Party\ContactDetail;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class ContactDetailTest extends TestCase
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
    public function archiving_frees_a_place_that_restoring_takes_again(): void
    {
        $this->limitActive('contact', 1);
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(ContactDetail::class, ['contactId' => $contact->id])
            ->call('archive')
            ->assertSet('successMessage', __('contacts.messages.archived'));
        $this->assertNotNull($contact->fresh()?->archived_at);

        $replacement = Contact::factory()->create(['user_id' => $this->user->id]);
        Livewire::test(ContactDetail::class, ['contactId' => $contact->id])
            ->call('restore')
            ->assertSet('errorMessage', __('contacts.messages.limit_exceeded'));
        $this->assertNotNull($contact->fresh()?->archived_at);

        $replacement->forceFill(['archived_at' => now()])->save();
        Livewire::test(ContactDetail::class, ['contactId' => $contact->id])->call('restore');
        $this->assertNull($contact->fresh()?->archived_at);
    }

    #[Test]
    public function shows_the_contact_person(): void
    {
        $contact = Contact::factory()->create([
            'user_id' => $this->user->id,
            'contact_person' => 'Eva Malá',
        ]);

        Livewire::test(ContactDetail::class, ['contactId' => $contact->id])->assertSee('Eva Malá');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Party;

use App\Domain\User\Enums\LimitedEntity;
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
        $this->limitFrontendUser(LimitedEntity::CONTACT, 1);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->user->id]);

        $component = Livewire::test(ContactDetail::class, ['contactId' => $contact->id]);
        $component->call('archive');
        $component->assertSet(
            'successMessage',
            __('contacts.messages.archived'),
        );
        $this->assertNotNull($contact->fresh()?->archived_at);

        $replacement = $contactFactory->create(['user_id' => $this->user->id]);
        $component = Livewire::test(ContactDetail::class, ['contactId' => $contact->id]);
        $component->call('restore');
        $component->assertSet(
            'limitMessage',
            __('contacts.messages.limit_exceeded', ['limit' => 1]),
        );
        $this->assertNotNull($contact->fresh()?->archived_at);

        $replacement->forceFill(['archived_at' => now()]);
        $replacement->save();
        $component = Livewire::test(ContactDetail::class, ['contactId' => $contact->id]);
        $component->call('restore');
        $this->assertNull($contact->fresh()?->archived_at);
    }

    #[Test]
    public function shows_the_contact_person(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            [
                'user_id' => $this->user->id,
                'contact_person' => 'Eva Malá',
            ],
        );

        $component = Livewire::test(ContactDetail::class, ['contactId' => $contact->id]);
        $component->assertSee('Eva Malá');
    }
}

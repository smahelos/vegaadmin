<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

/**
 * Saving the customer of a stored invoice to the contacts and linking it to an existing contact.
 */
final class InvoiceContactControllerTest extends TestCase
{
    use BuildsParties;

    private const CUSTOMER_NUMBER = '27082440';

    private const OTHER_NUMBER = '45317054';

    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
    }

    #[Test]
    public function a_guest_is_sent_to_the_login(): void
    {
        $invoice = $this->invoiceOf(User::factory()->create());

        $this->post(route('frontend.invoices.save-to-contacts', ['invoice' => $invoice->id]))->assertRedirect(route('frontend.login'));
        $this->post(route('frontend.invoices.link-contact', ['invoice' => $invoice->id]))->assertRedirect(route('frontend.login'));
    }

    #[Test]
    public function a_user_without_the_permissions_is_forbidden(): void
    {
        $user = User::factory()->create();
        $invoice = $this->invoiceOf($user);

        $this->actingAs($user)
            ->post(route('frontend.invoices.save-to-contacts', ['invoice' => $invoice->id]))
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('frontend.invoices.link-contact', ['invoice' => $invoice->id]))
            ->assertForbidden();
        $this->assertSame(0, Contact::query()->count());
    }

    #[Test]
    public function another_users_invoice_is_not_found(): void
    {
        $user = $this->frontendUser();
        $foreign = $this->invoiceOf(User::factory()->create());
        $contact = $this->contactOf($user, self::CUSTOMER_NUMBER);

        $this->actingAs($user)
            ->post(route('frontend.invoices.save-to-contacts', ['invoice' => $foreign->id]))
            ->assertNotFound();
        $this->actingAs($user)
            ->post(route('frontend.invoices.link-contact', ['invoice' => $foreign->id]), ['contact_id' => $contact->id])
            ->assertNotFound();
        $this->assertNull($foreign->fresh()?->contact_id);
    }

    #[Test]
    public function the_customer_is_saved_to_the_contacts_and_linked_to_the_invoice(): void
    {
        $user = $this->frontendUser();
        $invoice = $this->invoiceOf($user);

        $this->actingAs($user)
            ->from(route('frontend.invoice.show', ['id' => $invoice->id]))
            ->post(route('frontend.invoices.save-to-contacts', ['invoice' => $invoice->id]))
            ->assertRedirect(route('frontend.invoice.show', ['id' => $invoice->id]))
            ->assertSessionHas('success', __('invoices.messages.saved_to_contacts'));

        $contact = Contact::query()->where('user_id', $user->id)->sole();
        $this->assertSame('Odběratel s.r.o.', $contact->name);
        $this->assertSame(self::CUSTOMER_NUMBER, $contact->company_number);
        $this->assertSame($contact->id, $invoice->fresh()?->contact_id);
    }

    #[Test]
    public function repeated_save_to_contacts_requests_keep_the_existing_contact(): void
    {
        $user = $this->frontendUser();
        $invoice = $this->invoiceOf($user);
        $invoice->update(['customer_company_number' => null]);
        $this->actingAs($user);
        $this->post(route('frontend.invoices.save-to-contacts', ['invoice' => $invoice->id]))->assertSessionHasNoErrors();
        $contactId = $invoice->fresh()->contact_id;
        $this->post(route('frontend.invoices.save-to-contacts', ['invoice' => $invoice->id]))->assertSessionHasNoErrors();
        $this->assertSame($contactId, $invoice->fresh()->contact_id);
        $this->assertSame(1, Contact::where('user_id', $user->id)->count());
    }

    #[Test]
    public function the_invoice_is_linked_to_a_contact_with_the_customers_company_number(): void
    {
        $user = $this->frontendUser();
        $invoice = $this->invoiceOf($user);
        $contact = $this->contactOf($user, self::CUSTOMER_NUMBER);

        $this->actingAs($user)
            ->post(route('frontend.invoices.link-contact', ['invoice' => $invoice->id]), ['contact_id' => $contact->id])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', __('invoices.messages.contact_linked'));

        $this->assertSame($contact->id, $invoice->fresh()?->contact_id);
        $this->assertSame('Odběratel s.r.o.', $invoice->fresh()?->customer_name);
    }

    #[Test]
    public function a_contact_with_another_company_number_is_not_linked(): void
    {
        $user = $this->frontendUser();
        $invoice = $this->invoiceOf($user);
        $contact = $this->contactOf($user, self::OTHER_NUMBER);

        $this->actingAs($user)
            ->post(route('frontend.invoices.link-contact', ['invoice' => $invoice->id]), ['contact_id' => $contact->id])
            ->assertSessionHasErrors(['customer_company_number' => __('parties.validation.contact_identity_changed')]);

        $this->assertNull($invoice->fresh()?->contact_id);
    }

    #[Test]
    public function another_users_contact_is_rejected(): void
    {
        $user = $this->frontendUser();
        $invoice = $this->invoiceOf($user);
        $foreign = $this->contactOf(User::factory()->create(), self::CUSTOMER_NUMBER);

        $this->actingAs($user)
            ->post(route('frontend.invoices.link-contact', ['invoice' => $invoice->id]), ['contact_id' => $foreign->id])
            ->assertSessionHasErrors('contact_id');

        $this->assertNull($invoice->fresh()?->contact_id);
    }

    private function invoiceOf(User $user): Invoice
    {
        return Invoice::factory()->create([
            'user_id' => $user->id,
            'customer_name' => 'Odběratel s.r.o.',
            'customer_company_number' => self::CUSTOMER_NUMBER,
            'customer_registration_country' => 'CZ',
        ]);
    }

    private function contactOf(User $user, string $companyNumber): Contact
    {
        return Contact::factory()->create([
            'user_id' => $user->id,
            'company_number' => $companyNumber,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Services\Specialized\InvoiceFormApplicationService;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceFormApplicationServiceTest extends TestCase
{
    private const COMPANY_NUMBER = '27074358';

    private const OTHER_COMPANY_NUMBER = '45274649';

    #[Test]
    public function an_unlinked_customer_offers_the_contact_with_the_same_company_number(): void
    {
        [$user, $contact] = $this->userWithContact();

        $link = $this->contactToLink(
            $user,
            ['contact_id' => null, 'customer_company_number' => self::COMPANY_NUMBER],
        );

        $this->assertNotNull($link);
        $this->assertSame($contact->id, $link->id->value);
    }

    #[Test]
    public function an_unlinked_customer_with_an_unknown_company_number_offers_nothing(): void
    {
        [$user] = $this->userWithContact();

        $link = $this->contactToLink(
            $user,
            ['contact_id' => null, 'customer_company_number' => self::OTHER_COMPANY_NUMBER],
        );

        $this->assertNull($link);
    }

    #[Test]
    public function a_linked_customer_offers_nothing(): void
    {
        [$user, $contact] = $this->userWithContact();

        $link = $this->contactToLink(
            $user,
            ['contact_id' => $contact->id, 'customer_company_number' => self::COMPANY_NUMBER],
        );

        $this->assertNull($link);
    }

    /** @return array{User, Contact} */
    private function userWithContact(): array
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        // The offer reads the contacts through FindContactDuplicates
        $user->assignRole('frontend_user');
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            [
                'user_id' => $user->id,
                'registration_country' => 'CZ',
                'company_number' => self::COMPANY_NUMBER,
            ],
        );

        return [$user, $contact];
    }

    /** @param array<string, mixed> $attributes */
    private function contactToLink(User $user, array $attributes): ?DuplicatePartyDTO
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            $attributes + ['user_id' => $user->id, 'customer_registration_country' => 'CZ'],
        );
        $service = app(InvoiceFormApplicationService::class);
        $form = $service->prepareShowData($user->id, $invoice->id);
        $data = $form->toArray();

        return $data['contactToLink'];
    }
}

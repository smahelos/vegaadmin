<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice;

use App\Application\Invoice\Commands\CreateGuestInvoice;
use App\Application\Invoice\Commands\CreateInvoice;
use App\Application\Invoice\Commands\DeleteGuestInvoice;
use App\Application\Invoice\Commands\MarkInvoicePaid;
use App\Application\Invoice\Commands\SaveInvoiceCustomerToContacts;
use App\Application\Invoice\Commands\SetInvoiceTemplate;
use App\Application\Invoice\Commands\UpdateInvoice;
use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Application\Invoice\Queries\GetGuestInvoicePdf;
use App\Application\Invoice\Queries\GetInvoice;
use App\Application\Invoice\Queries\GetInvoiceCreateForm;
use App\Application\Invoice\Queries\GetInvoicePdf;
use App\Application\Invoice\Queries\ListInvoiceFilters;
use App\Application\Invoice\Queries\ListInvoices;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Domain\Invoice\DTO\InvoiceProductDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceSearch;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Address;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\Support\BuildsInvoiceInput;
use Tests\Support\BuildsParties;
use Tests\Support\RunsUseCases;
use Tests\TestCase;

final class InvoiceUseCasesTest extends TestCase
{
    use BuildsInvoiceInput;
    use BuildsParties;
    use RunsUseCases;

    private const COMPANY_NUMBER = '27074358';

    private const OTHER_COMPANY_NUMBER = '45274649';

    #[Test]
    public function a_failure_while_storing_the_lines_leaves_the_invoice_and_its_lines_untouched(): void
    {
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'invoice_vs' => '20260001',
                'payment_currency' => 'CZK',
            ],
        );
        $invoiceProductFactory = InvoiceProduct::factory();
        $line = $invoiceProductFactory->create(['invoice_id' => $invoice->id]);
        $this->app->bind(InvoiceProductDtoWriteRepositoryInterface::class, fn () => $this->failingLineRepository());

        try {
            $this->runAs(
                $this->idOf($user),
                new UpdateInvoice(
                    $this->idOf($user),
                    InvoiceId::fromInt($invoice->id),
                    $this->invoiceWriteData('20260002'),
                    $this->parties(
                        BusinessId::fromInt($invoice->business_id),
                        new DocumentPartyDTO(
                            'Odběratel s.r.o.',
                            null,
                            null,
                            $this->brnoAddress(),
                            null,
                            null,
                        ),
                        false,
                    ),
                    [$this->line()],
                ),
            );
            $this->fail('The update did not fail');
        } catch (RuntimeException $exception) {
            $this->assertSame('Storing the lines failed', $exception->getMessage());
        }

        $this->assertSame('20260001', $invoice->fresh()->invoice_vs);
        $invoiceProductQuery = InvoiceProduct::where('invoice_id', $invoice->id);
        $invoiceProductValues = $invoiceProductQuery->pluck('id');
        $this->assertSame(
            [$line->id],
            $invoiceProductValues->all(),
        );
    }

    #[Test]
    public function a_later_line_failure_rolls_back_the_new_contact_address_and_invoice(): void
    {
        $user = $this->frontendUser();
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $user->id]);
        $addressFactory = Address::factory();
        $addressFactory->create(['business_id' => $business->id]);
        $beforeAddresses = Address::count();
        $this->app->bind(InvoiceProductDtoWriteRepositoryInterface::class, fn () => $this->failingLineRepository());

        try {
            $this->runAs(
                $this->idOf($user),
                new CreateInvoice(
                    $this->idOf($user),
                    $this->invoiceWriteData('20260042'),
                    $this->parties(
                        BusinessId::fromInt($business->id),
                        new DocumentPartyDTO(
                            'Atomic customer',
                            null,
                            null,
                            $this->brnoAddress(),
                            null,
                            null,
                        ),
                        true,
                    ),
                    [$this->line()],
                ),
            );
            $this->fail('The line write did not fail');
        } catch (RuntimeException $exception) {
            $this->assertSame('Storing the lines failed', $exception->getMessage());
        }

        $contactQuery = Contact::where('user_id', $user->id);
        $this->assertSame(
            0,
            $contactQuery->count(),
        );
        $invoiceQuery = Invoice::where('user_id', $user->id);
        $this->assertSame(
            0,
            $invoiceQuery->count(),
        );
        $this->assertSame($beforeAddresses, Address::count());
    }

    #[Test]
    public function saving_the_customer_to_the_contacts_needs_the_contact_permission(): void
    {
        $roleQuery = Role::query();
        $roleQuery->where('name', 'frontend_user');
        $role = $roleQuery->sole();
        $role->revokePermissionTo(Permission::CONTACT_CREATE);
        $user = $this->frontendUser();
        [$business] = $this->issuerOf($user);

        try {
            $this->runAs(
                $this->idOf($user),
                new CreateInvoice(
                    $this->idOf($user),
                    $this->invoiceWriteData(),
                    $this->parties(
                        BusinessId::fromInt($business->id),
                        new DocumentPartyDTO(
                            'Customer',
                            null,
                            null,
                            $this->brnoAddress(),
                            null,
                            null,
                        ),
                        true,
                    ),
                    [$this->line()],
                ),
            );
            $this->fail('The contact was saved without the permission');
        } catch (PermissionDeniedException) {
        }

        $this->assertDatabaseMissing('invoices', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('contacts', ['user_id' => $user->id]);
    }

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

    #[Test]
    public function a_contact_link_committed_by_another_request_is_seen_even_after_an_earlier_snapshot(): void
    {
        config(
            ['database.connections.contact_race' => config(
                'database.connections.' . config('database.default'),
            )],
        );
        $connection = DB::connection('contact_race');
        $userFactory = User::factory();
        $userFactory = $userFactory->connection('contact_race');
        $owner = $userFactory->create();
        $this->beforeApplicationDestroyed(
            function () use ($connection, $owner): void {
                $query = $connection->table('invoices');
                $query->where('user_id', $owner->id);
                $query->delete();
                $query = $connection->table('contacts');
                $query->where('user_id', $owner->id);
                $query->delete();
                $query = $connection->table('businesses');
                $query->where('user_id', $owner->id);
                $query->delete();
                $query = $connection->table('model_has_roles');
                $query->where('model_type', User::class);
                $query->where('model_id', $owner->id);
                $query->delete();
                $query = $connection->table('users');
                $query->where('id', $owner->id);
                $query->delete();
            },
        );
        $roleQuery = Role::on('contact_race');
        $roleQuery->where('name', 'frontend_user');
        $roleQuery->where('guard_name', 'web');
        $owner->assignRole(
            $roleQuery->firstOrFail(),
        );
        $businessFactory = Business::factory();
        $businessFactory = $businessFactory->connection('contact_race');
        $business = $businessFactory->create(['user_id' => $owner->id]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory = $invoiceFactory->connection('contact_race');
        $paymentMethodQuery = PaymentMethod::on('contact_race');
        $paymentMethodQuery->where('slug', 'cash');
        $statusQuery = Status::on('contact_race');
        $statusQuery->where('slug', 'pending');
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $owner->id,
                'business_id' => $business->id,
                'payment_method_id' => $paymentMethodQuery->value('id'),
                'payment_status_id' => $statusQuery->value('id'),
                'customer_company_number' => null,
            ],
        );
        $invoiceQuery = Invoice::query();
        $this->assertNull(
            $invoiceQuery->find($invoice->id)?->contact_id,
        );
        $contactFactory = Contact::factory();
        $contactFactory = $contactFactory->connection('contact_race');
        $contact = $contactFactory->create(['user_id' => $owner->id, 'company_number' => null]);
        $invoice->update(['contact_id' => $contact->id]);
        // A non-locking reread still sees the earlier snapshot after the other request commits.
        $invoiceQuery = Invoice::query();
        $this->assertNull(
            $invoiceQuery->find($invoice->id)?->contact_id,
        );

        $this->runAs(
            UserId::fromInt($owner->id),
            new SaveInvoiceCustomerToContacts(
                UserId::fromInt($owner->id),
                InvoiceId::fromInt($invoice->id),
            ),
        );

        $contactQuery = Contact::where('user_id', $owner->id);
        $contactQuery->lockForUpdate();
        $this->assertCount(
            1,
            $contactQuery->get(),
        );
        $invoiceQuery = Invoice::query();
        $invoiceQuery->lockForUpdate();
        $this->assertSame(
            $contact->id,
            $invoiceQuery->find($invoice->id)?->contact_id,
        );
    }

    #[Test]
    public function the_owner_sets_the_template_and_marks_the_invoice_paid(): void
    {
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(['user_id' => $user->id]);
        $id = InvoiceId::fromInt($invoice->id);

        $this->runAs(
            $this->idOf($user),
            new SetInvoiceTemplate(
                $this->idOf($user),
                $id,
                InvoiceTemplate::MODERN,
            ),
        );
        $this->runAs(
            $this->idOf($user),
            new MarkInvoicePaid(
                $this->idOf($user),
                $id,
            ),
        );

        $statusQuery = Status::where('slug', InvoiceStatus::PAID->value);
        $this->assertDatabaseHas(
            'invoices',
            [
                'id' => $invoice->id,
                'template' => InvoiceTemplate::MODERN->value,
                'payment_status_id' => $statusQuery->value('id'),
            ],
        );
    }

    #[Test]
    public function another_users_invoice_cannot_be_changed(): void
    {
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(['user_id' => $user->id]);
        $other = $this->frontendUser();

        $this->expectException(InvoiceNotFoundException::class);

        $this->runAs(
            $this->idOf($other),
            new MarkInvoicePaid(
                $this->idOf($other),
                InvoiceId::fromInt($invoice->id),
            ),
        );
    }

    #[Test]
    public function the_owner_gets_the_pdf(): void
    {
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(['user_id' => $user->id, 'payment_currency' => 'EUR']);
        $invoiceProductFactory = InvoiceProduct::factory();
        $invoiceProductFactory->create(['invoice_id' => $invoice->id, 'currency' => 'EUR']);

        $pdf = $this->runAs(
            $this->idOf($user),
            new GetInvoicePdf(
                $this->idOf($user),
                InvoiceId::fromInt($invoice->id),
                'en',
            ),
        );

        $this->assertStringStartsWith('%PDF', $pdf->content);
        $this->assertSame('faktura-' . $invoice->invoice_vs . '.pdf', $pdf->filename);
    }

    #[Test]
    public function another_users_invoice_is_not_rendered(): void
    {
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(['user_id' => $user->id]);
        $other = $this->frontendUser();

        $this->expectException(InvoiceNotFoundException::class);

        $this->runAs(
            $this->idOf($other),
            new GetInvoicePdf(
                $this->idOf($other),
                InvoiceId::fromInt($invoice->id),
                'cs',
            ),
        );
    }

    #[Test]
    public function a_guest_gets_the_pdf_of_the_stored_invoice_by_its_token(): void
    {
        $token = $this->storeGuestInvoice();

        $pdf = $this->runAnonymously(
            new GetGuestInvoicePdf($token, 'en'),
        );

        $this->assertStringStartsWith('%PDF', $pdf->content);
    }

    #[Test]
    public function an_unknown_guest_token_is_not_found(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $this->runAnonymously(
            new GetGuestInvoicePdf('unknown-token', 'cs'),
        );
    }

    #[Test]
    public function a_guest_deletes_only_the_invoice_of_the_own_session(): void
    {
        $token = $this->storeGuestInvoice();
        $store = $this->app->make(TemporaryInvoiceStoreInterface::class);

        try {
            $this->runAnonymously(
                new DeleteGuestInvoice($token, 'another-session-token'),
            );
            $this->fail('A foreign token was deleted');
        } catch (InvoiceNotFoundException) {
        }
        $this->assertNotNull(
            $store->get($token),
        );

        $this->runAnonymously(
            new DeleteGuestInvoice($token, $token),
        );

        $this->assertNull(
            $store->get($token),
        );
    }

    #[Test]
    public function list_filters_offer_only_the_owners_parties_and_reject_a_foreign_one(): void
    {
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(['user_id' => $user->id]);
        $other = $this->frontendUser();
        $otherInvoice = $invoiceFactory->create(['user_id' => $other->id]);

        $filters = $this->runAs(
            $this->idOf($user),
            new ListInvoiceFilters(
                $this->idOf($user),
            ),
        );
        $this->assertSame(
            [$invoice->business_id],
            array_map(fn ($filter): int => $filter->id->value, $filters->businesses),
        );

        $this->expectException(BusinessNotFoundException::class);
        $this->runAs(
            $this->idOf($user),
            new ListInvoices(
                $this->idOf($user),
                $this->search(
                    BusinessId::fromInt($otherInvoice->business_id),
                ),
                new PageRequest,
            ),
        );
    }

    #[Test]
    public function the_create_form_suggests_starts_sequence_for_user_without_invoices(): void
    {
        $now = CarbonImmutable::now();
        $year = $now->year;
        $user = $this->frontendUser();

        $this->assertSame(
            $year . '0001',
            $this->suggestedNumber($user),
        );
    }

    #[Test]
    public function the_create_form_suggests_continues_sequence_of_current_year(): void
    {
        $now = CarbonImmutable::now();
        $year = $now->year;
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(['user_id' => $user->id, 'invoice_vs' => $year . '0007']);
        $invoiceFactory->create(['user_id' => $user->id, 'invoice_vs' => $year . '0012']);

        $this->assertSame(
            $year . '0013',
            $this->suggestedNumber($user),
        );
    }

    #[Test]
    public function the_create_form_suggests_restarts_sequence_after_non_standard_last_number(): void
    {
        $now = CarbonImmutable::now();
        $year = $now->year;
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(['user_id' => $user->id, 'invoice_vs' => $year . '-A1']);

        $this->assertSame(
            $year . '0001',
            $this->suggestedNumber($user),
        );
    }

    #[Test]
    public function the_create_form_suggests_continues_past_9999(): void
    {
        $now = CarbonImmutable::now();
        $year = $now->year;
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(['user_id' => $user->id, 'invoice_vs' => $year . '9999']);
        $invoiceFactory->create(['user_id' => $user->id, 'invoice_vs' => $year . '10000']);

        $this->assertSame(
            $year . '10001',
            $this->suggestedNumber($user),
        );
    }

    #[Test]
    public function the_create_form_suggests_ignores_non_standard_numbers(): void
    {
        $now = CarbonImmutable::now();
        $year = $now->year;
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(['user_id' => $user->id, 'invoice_vs' => $year . '0005']);
        $invoiceFactory->create(
            ['user_id' => $user->id, 'invoice_vs' => $year . '-ZZZ-LONG-NUMBER'],
        );

        $this->assertSame(
            $year . '0006',
            $this->suggestedNumber($user),
        );
    }

    /** @return list<array{string}> */
    public static function reads(): array
    {
        return [
            ['invoices'],
            ['filters'],
        ];
    }

    #[Test]
    #[DataProvider('reads')]
    public function every_list_read_requires_the_invoice_view_permission(string $read): void
    {
        $userFactory = User::factory();
        $storedUser = $userFactory->create();
        $user = UserId::fromInt($storedUser->id);

        $this->expectException(PermissionDeniedException::class);

        $this->runAs(
            $user,
            match ($read) {
                'invoices' => new ListInvoices(
                    $user,
                    $this->search(null),
                    new PageRequest,
                ),
                'filters' => new ListInvoiceFilters($user),
            },
        );
    }

    private function suggestedNumber(User $user): string
    {
        $form = $this->runAs(
            $this->idOf($user),
            new GetInvoiceCreateForm(
                $this->idOf($user),
            ),
        );
        $data = $form->toArray();

        return $data['suggestedNumber'];
    }

    private function search(?BusinessId $businessId): InvoiceSearch
    {
        return new InvoiceSearch(
            '',
            null,
            $businessId,
            null,
            'created_at',
            false,
        );
    }

    private function parties(
        BusinessId $business,
        DocumentPartyDTO $customer,
        bool $saveCustomer,
    ): InvoicePartySelection {
        return new InvoicePartySelection(
            $business,
            null,
            null,
            $customer,
            $saveCustomer,
            false,
        );
    }

    private function line(): InvoiceProductWriteData
    {
        return new InvoiceProductWriteData(
            'Line',
            '1',
            Unit::PIECES,
            Money::fromString('10', 'CZK'),
            '21',
        );
    }

    private function storeGuestInvoice(): string
    {
        $party = new DocumentPartyDTO(
            'Dodavatel s.r.o.',
            null,
            null,
            $this->brnoAddress(),
            null,
            null,
        );
        $result = $this->runAnonymously(
            new CreateGuestInvoice(
                $this->guestValidated(),
                $party,
                $party,
                null,
                'en',
            ),
        );

        return $result->token;
    }

    /**
     * Validated input of a guest invoice, as the form request hands it over.
     *
     * @return array<string, mixed>
     */
    private function guestValidated(): array
    {
        $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value);
        $statusQuery = Status::where('slug', InvoiceStatus::PENDING->value);
        $now = CarbonImmutable::now();
        $today = $now->format('Y-m-d');

        return [
            'invoice_vs' => $this->invoiceNumber(),
            'payment_method_id' => $paymentMethodQuery->value('id'),
            'payment_currency' => 'EUR',
            'issue_date' => $today,
            'tax_point_date' => $today,
            'due_in' => 14,
            'payment_status_id' => $statusQuery->value('id'),
            'invoice_text' => 'Test invoice',
            'invoice-products' => [
                [
                    'name' => 'Service A',
                    'quantity' => '2',
                    'unit' => 'pieces',
                    'price' => '50',
                    'currency' => 'EUR',
                    'tax_rate' => '21',
                ],
            ],
        ];
    }

    /**
     * @template TResult
     *
     * @param  CommandInterface<TResult>|QueryInterface<TResult>  $useCase
     * @return TResult
     */
    private function runAnonymously(CommandInterface|QueryInterface $useCase): mixed
    {
        $useCases = $this->app->make(AuthorizingUseCaseRunnerInterface::class);

        return $useCases->run(
            Actor::anonymous(),
            $useCase,
        );
    }

    /** @return array{User, Contact} */
    private function userWithContact(): array
    {
        $user = $this->frontendUser();
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
        $form = $this->runAs(
            $this->idOf($user),
            new GetInvoice(
                $this->idOf($user),
                InvoiceId::fromInt($invoice->id),
            ),
        );
        $data = $form->toArray();

        return $data['contactToLink'];
    }

    /**
     * Deletes the old lines for real, then fails before the new ones are stored.
     */
    private function failingLineRepository(): InvoiceProductDtoWriteRepositoryInterface
    {
        return new class implements InvoiceProductDtoWriteRepositoryInterface
        {
            public function create(InvoiceId $invoiceId, InvoiceProductWriteData $data): InvoiceProductDTO
            {
                throw new RuntimeException('Storing a line failed');
            }

            public function bulkCreate(InvoiceId $invoiceId, array $products): void
            {
                throw new RuntimeException('Storing the lines failed');
            }

            public function deleteByInvoiceId(InvoiceId $invoiceId): void
            {
                $invoiceProductQuery = InvoiceProduct::where('invoice_id', $invoiceId->getValue());
                $invoiceProductQuery->delete();
            }

            public function allForInvoice(InvoiceId $invoiceId): array
            {
                return [];
            }

            public function listForInvoiceWithProduct(InvoiceId $invoiceId): array
            {
                return [];
            }
        };
    }
}

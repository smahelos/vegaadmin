<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice;

use App\Application\Invoice\Commands\CreateGuestInvoice;
use App\Application\Invoice\Commands\CreateInvoice;
use App\Application\Invoice\Commands\DeleteGuestInvoice;
use App\Application\Invoice\Commands\SaveInvoiceCustomerToContacts;
use App\Application\Invoice\Commands\SetInvoiceStatus;
use App\Application\Invoice\Commands\SetInvoiceTemplate;
use App\Application\Invoice\Commands\UpdateInvoice;
use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Application\Invoice\Queries\GetGuestInvoicePdf;
use App\Application\Invoice\Queries\GetInvoice;
use App\Application\Invoice\Queries\GetInvoicePdf;
use App\Application\Invoice\Queries\GetIssueInvoiceForm;
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
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\Enums\Currency;
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
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
        $draftFactory = $invoiceFactory->draft();
        $invoice = $draftFactory->create(
            [
                'user_id' => $user->id,
                'due_in' => 30,
                'payment_currency' => 'CZK',
            ],
        );
        $invoiceProductFactory = InvoiceProduct::factory();
        $line = $invoiceProductFactory->create(['invoice_id' => $invoice->id]);

        try {
            $this->runAs(
                $this->idOf($user),
                new UpdateInvoice(
                    $this->idOf($user),
                    InvoiceId::fromInt($invoice->id),
                    $this->invoiceWriteData(),
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
                    [$this->line(), $this->rejectedLine()],
                ),
            );
            $this->fail('The update did not fail');
        } catch (QueryException) {
        }

        $this->assertSame(30, $invoice->fresh()->due_in);
        $invoiceProductQuery = InvoiceProduct::where('invoice_id', $invoice->id);
        $invoiceProductValues = $invoiceProductQuery->pluck('id');
        $this->assertSame(
            [$line->id],
            $invoiceProductValues->all(),
        );
    }

    #[Test]
    public function creating_an_invoice_stores_its_lines_and_returns_them_with_the_totals(): void
    {
        $user = $this->frontendUser();
        [$business] = $this->issuerOf($user);
        $productFactory = Product::factory();
        $product = $productFactory->create(['user_id' => $user->id]);

        $invoice = $this->runAs(
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
                    false,
                ),
                [
                    new InvoiceProductWriteData(
                        name: 'Consulting',
                        quantity: '1.5',
                        unit: Unit::HOURS,
                        price: Money::fromString('249.30', Currency::CZK),
                        taxRate: '12',
                        category: 'Service',
                    ),
                    new InvoiceProductWriteData(
                        name: 'Existing Product',
                        quantity: '3',
                        unit: Unit::PIECES,
                        price: Money::fromString('74.67', Currency::CZK),
                        taxRate: '21',
                        productId: ProductId::fromInt($product->id),
                    ),
                ],
            ),
        );

        $this->assertSame(
            ['Consulting', 'Existing Product'],
            $this->lineNames($invoice->items),
        );
        $this->assertTrue(
            $invoice->totals->subtotal->equals(
                Money::fromString('597.96', Currency::CZK),
            ),
        );
        $this->assertTrue(
            $invoice->totals->tax->equals(
                Money::fromString('91.91', Currency::CZK),
            ),
        );
        $this->assertTrue(
            $invoice->totalAmount->equals(
                Money::fromString('689.87', Currency::CZK),
            ),
        );
        $this->assertDatabaseHas(
            'invoice_products',
            [
                'invoice_id' => $invoice->id->value,
                'name' => 'Consulting',
                'product_id' => null,
                'is_custom_product' => true,
                'quantity' => '1.500',
                'unit' => 'hours',
                'price' => '249.30',
                'tax_rate' => '12.00',
                'category' => 'Service',
            ],
        );
        $this->assertDatabaseHas(
            'invoice_products',
            [
                'invoice_id' => $invoice->id->value,
                'product_id' => $product->id,
                'is_custom_product' => false,
                'price' => '74.67',
            ],
        );
    }

    #[Test]
    public function updating_an_invoice_replaces_its_lines_and_returns_the_new_ones(): void
    {
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $draftFactory = $invoiceFactory->draft();
        $invoice = $draftFactory->create(
            [
                'user_id' => $user->id,
                'payment_currency' => 'CZK',
            ],
        );
        $invoiceProductFactory = InvoiceProduct::factory();
        $oldLine = $invoiceProductFactory->create(['invoice_id' => $invoice->id]);

        $updated = $this->runAs(
            $this->idOf($user),
            new UpdateInvoice(
                $this->idOf($user),
                InvoiceId::fromInt($invoice->id),
                $this->invoiceWriteData(),
                $this->parties(
                    BusinessId::fromInt($invoice->business_id),
                    new DocumentPartyDTO(
                        'Customer',
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

        $this->assertSame(
            ['Line'],
            $this->lineNames($updated->items),
        );
        $this->assertTrue(
            $updated->totals->subtotal->equals(
                Money::fromString('10', Currency::CZK),
            ),
        );
        $this->assertTrue(
            $updated->totalAmount->equals(
                Money::fromString('10', Currency::CZK),
            ),
        );
        $this->assertDatabaseMissing('invoice_products', ['id' => $oldLine->id]);
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

        try {
            $this->runAs(
                $this->idOf($user),
                new CreateInvoice(
                    $this->idOf($user),
                    $this->invoiceWriteData(),
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
                    [$this->line(), $this->rejectedLine()],
                ),
            );
            $this->fail('The line write did not fail');
        } catch (QueryException) {
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
        $invoiceFactory = $invoiceFactory->draft();
        $invoiceFactory = $invoiceFactory->connection('contact_race');
        $paymentMethodQuery = PaymentMethod::on('contact_race');
        $paymentMethodQuery->where('slug', 'cash');
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $owner->id,
                'business_id' => $business->id,
                'payment_method_id' => $paymentMethodQuery->value('id'),
                'payment_status' => InvoiceStatus::PENDING->value,
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
    public function the_owner_sets_the_template_of_a_draft(): void
    {
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $draftFactory = $invoiceFactory->draft();
        $invoice = $draftFactory->create(['user_id' => $user->id]);

        $this->runAs(
            $this->idOf($user),
            new SetInvoiceTemplate(
                $this->idOf($user),
                InvoiceId::fromInt($invoice->id),
                InvoiceTemplate::MODERN,
            ),
        );

        $this->assertDatabaseHas(
            'invoices',
            [
                'id' => $invoice->id,
                'template' => InvoiceTemplate::MODERN->value,
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
            new SetInvoiceStatus(
                $this->idOf($other),
                InvoiceId::fromInt($invoice->id),
                InvoiceStatus::PAID,
            ),
        );
    }

    #[Test]
    public function the_owner_gets_the_pdf(): void
    {
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoiceFactory = $invoiceFactory->withStoredPdf();
        $invoice = $invoiceFactory->create(['user_id' => $user->id, 'payment_currency' => 'EUR']);

        $pdf = $this->runAs(
            $this->idOf($user),
            new GetInvoicePdf(
                $this->idOf($user),
                InvoiceId::fromInt($invoice->id),
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
    public function the_issue_dialog_suggests_starts_sequence_for_user_without_invoices(): void
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
    public function the_issue_dialog_suggests_continues_sequence_of_current_year(): void
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
    public function the_issue_dialog_suggests_restarts_sequence_after_non_standard_last_number(): void
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
    public function the_issue_dialog_suggests_continues_past_9999(): void
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
    public function the_issue_dialog_suggests_ignores_non_standard_numbers(): void
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
        $invoiceFactory = Invoice::factory();
        $draftFactory = $invoiceFactory->draft();
        $draft = $draftFactory->create(['user_id' => $user->id]);
        $form = $this->runAs(
            $this->idOf($user),
            new GetIssueInvoiceForm(
                $this->idOf($user),
                InvoiceId::fromInt($draft->id),
            ),
        );

        return $form->number->value;
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
            Money::fromString('10', Currency::CZK),
            '0',
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
        $now = CarbonImmutable::now();
        $today = $now->format('Y-m-d');

        return [
            'invoice_vs' => $this->invoiceNumber(),
            'payment_method_id' => $paymentMethodQuery->value('id'),
            'payment_currency' => 'EUR',
            'issue_date' => $today,
            'tax_point_date' => $today,
            'due_in' => 14,
            'invoice_text' => 'Test invoice',
            'template' => InvoiceTemplate::DEFAULT->value,
            'is_vat_payer' => true,
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
        $draftFactory = $invoiceFactory->draft();
        $invoice = $draftFactory->create(
            $attributes + ['user_id' => $user->id, 'customer_registration_country' => 'CZ'],
        );
        $detail = $this->runAs(
            $this->idOf($user),
            new GetInvoice(
                $this->idOf($user),
                InvoiceId::fromInt($invoice->id),
            ),
        );

        return $detail->contactToLink;
    }

    /**
     * @param  list<InvoiceProductDTO>  $items
     * @return list<string>
     */
    private function lineNames(array $items): array
    {
        $names = [];
        foreach ($items as $item) {
            $names[] = $item->name;
        }

        return $names;
    }

    /** A name longer than its column: MySQL refuses the line after the invoice row is written. */
    private function rejectedLine(): InvoiceProductWriteData
    {
        return new InvoiceProductWriteData(
            str_repeat('x', 256),
            '1',
            Unit::PIECES,
            Money::fromString('10', Currency::CZK),
            '0',
        );
    }
}

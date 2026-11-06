<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice\Services;

use App\Application\Invoice\Services\InvoiceContactApplicationService;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class InvoiceContactApplicationServiceTest extends TestCase
{
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
        $ownerId = UserId::fromInt($owner->id);
        $invoiceId = InvoiceId::fromInt($invoice->id);
        $reader = app(InvoiceDtoReadRepositoryInterface::class);
        $this->assertNull(
            $reader->findByIdForUser($invoiceId, $ownerId)->parties->contactId,
        );

        $contactFactory = Contact::factory();
        $contactFactory = $contactFactory->connection('contact_race');
        $contact = $contactFactory->create(['user_id' => $owner->id, 'company_number' => null]);
        $invoice->update(['contact_id' => $contact->id]);
        // A non-locking reread still sees the earlier snapshot after the other request commits.
        $this->assertNull(
            $reader->findByIdForUser($invoiceId, $ownerId)->parties->contactId,
        );

        $invoiceContactApplicationService = app(InvoiceContactApplicationService::class);
        $linked = $invoiceContactApplicationService->saveCustomerToContacts($ownerId, $invoiceId);

        $this->assertSame($contact->id, $linked->value);
        $contactQuery = Contact::where('user_id', $owner->id);
        $contactQuery->lockForUpdate();
        $this->assertCount(
            1,
            $contactQuery->get(),
        );
        $this->assertSame(
            $contact->id,
            $reader->findByIdForUserForUpdate($invoiceId, $ownerId)->parties->contactId->value,
        );
    }
}

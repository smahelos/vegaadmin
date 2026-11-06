<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice\Services;

use App\Application\Invoice\Services\ExpenseApplicationService;
use App\Domain\Invoice\DTO\ExpenseWriteData;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Contact;
use App\Models\ExpenseCategory;
use App\Models\User;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class ExpenseApplicationServiceTest extends TestCase
{
    use BuildsInvoiceInput;
    use CreatesAdminTestEnvironment;

    #[Test]
    public function creating_an_expense_rejects_the_contact_of_another_user(): void
    {
        $this->setUpAdminTestEnvironment();
        [$business] = $this->issuerOf($this->adminUser);
        $userFactory = User::factory();
        $other = $userFactory->create();
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $other->id]);
        $expenseCategoryFactory = ExpenseCategory::factory();
        $expenseCategory = $expenseCategoryFactory->create();
        $userId = UserId::fromInt($this->adminUser->id);

        $data = new ExpenseWriteData(
            BusinessId::fromInt($business->id),
            ContactId::fromInt($contact->id),
            null,
            new DocumentPartyDTO(
                'One-off supplier',
                null,
                null,
                null,
                null,
                null,
            ),
            null,
            new DateTimeImmutable('2026-01-15'),
            Money::fromString('123.45', 'CZK'),
            $expenseCategory->id,
            null,
            null,
            null,
            Money::fromString('0.00', 'CZK'),
            false,
            null,
        );
        $expenses = app(ExpenseApplicationService::class);

        $this->expectException(ContactNotFoundException::class);

        $expenses->create(
            $userId,
            $data,
            $userId,
        );
    }
}

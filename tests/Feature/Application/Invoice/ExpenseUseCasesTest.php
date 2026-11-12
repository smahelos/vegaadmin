<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice;

use App\Application\Invoice\Commands\CreateExpense;
use App\Domain\Invoice\DTO\ExpenseWriteData;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\Contact;
use App\Models\ExpenseCategory;
use App\Models\User;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsInvoiceInput;
use Tests\Support\BuildsParties;
use Tests\Support\RunsUseCases;
use Tests\TestCase;

/**
 * The contact rejection is reached only after the gate, so it also proves expense.create on the web guard.
 */
final class ExpenseUseCasesTest extends TestCase
{
    use BuildsInvoiceInput;
    use BuildsParties;
    use RunsUseCases;

    #[Test]
    public function creating_an_expense_rejects_the_contact_of_another_user(): void
    {
        $user = $this->frontendUser();
        [$business] = $this->issuerOf($user);
        $userFactory = User::factory();
        $other = $userFactory->create();
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $other->id]);
        $expenseCategoryFactory = ExpenseCategory::factory();
        $expenseCategory = $expenseCategoryFactory->create();

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

        $this->expectException(ContactNotFoundException::class);

        $this->runAs(
            $this->idOf($user),
            new CreateExpense(
                $this->idOf($user),
                $data,
            ),
        );
    }
}

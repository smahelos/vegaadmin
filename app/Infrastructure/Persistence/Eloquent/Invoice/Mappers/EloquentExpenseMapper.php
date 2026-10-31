<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Domain\Invoice\DTO\ExpenseDTO;
use App\Domain\Invoice\DTO\ExpenseWriteData;
use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentDocumentPartiesMapper;
use App\Models\Expense;

final class EloquentExpenseMapper
{
    public static function toDTO(Expense $expense): ExpenseDTO
    {
        return new ExpenseDTO(
            ExpenseId::fromInt($expense->id),
            UserId::fromInt($expense->user_id),
            EloquentDocumentPartiesMapper::toDocumentParties($expense),
            $expense->attachments ?? [],
        );
    }

    /**
     * @param  list<string>  $attachments
     * @return array<string, mixed>
     */
    public static function toAttributes(
        UserId $userId,
        ExpenseWriteData $data,
        DocumentParties $parties,
        array $attachments,
    ): array {
        return [
            ...EloquentDocumentPartiesMapper::toAttributes($parties),
            'user_id' => $userId->toInt(),
            'expense_date' => $data->expenseDate->format('Y-m-d'),
            'amount' => $data->amount->getAmount(),
            'currency' => $data->amount->getCurrency(),
            'category_id' => $data->categoryId,
            'payment_method_id' => $data->paymentMethodId,
            'reference_number' => $data->referenceNumber,
            'description' => $data->description,
            'tax_amount' => $data->taxAmount->getAmount(),
            'tax_included' => $data->taxIncluded,
            'status_id' => $data->statusId,
            'attachments' => $attachments,
        ];
    }
}

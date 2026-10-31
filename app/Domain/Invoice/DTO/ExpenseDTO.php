<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\User\ValueObjects\UserId;

final readonly class ExpenseDTO
{
    /** @param list<string> $attachments */
    public function __construct(
        public ExpenseId $id,
        public UserId $userId,
        public DocumentParties $parties,
        public array $attachments,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The owner's active businesses a document can take, searched by the term, or the one with the id.
 *
 * @implements QueryInterface<PaginatedResult<BusinessDTO>>
 */
final readonly class ListDocumentBusinesses extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public ?string $term,
        public ?BusinessId $id,
        public PageRequest $page,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::anyOf(
            Permission::INVOICE_CREATE,
            Permission::INVOICE_UPDATE,
            Permission::EXPENSE_CREATE,
            Permission::EXPENSE_UPDATE,
        );
    }
}

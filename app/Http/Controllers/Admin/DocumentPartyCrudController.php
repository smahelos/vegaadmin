<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Invoice\DTO\DocumentPartyOptionDTO;
use App\Application\Invoice\Queries\FindDefaultDocumentBankAccount;
use App\Application\Invoice\Queries\ListDocumentBankAccounts;
use App\Application\Invoice\Queries\ListDocumentBusinesses;
use App\Application\Invoice\Queries\ListDocumentContacts;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Http\Requests\Admin\DocumentPartySelectionRequest;
use App\Http\Resources\Admin\DocumentPartyOptionResource;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

final class DocumentPartyCrudController extends CrudController
{
    use ActsAsAdmin;

    private const PER_PAGE = 25;

    public function __construct(private readonly AuthorizingUseCaseRunnerInterface $useCases)
    {
        parent::__construct();
    }

    public function businesses(DocumentPartySelectionRequest $request): AnonymousResourceCollection
    {
        $id = $request->selectedId();
        // The route names the same use case in its `allowed` middleware: change them together
        $found = $this->useCases->run(
            $this->admin(),
            new ListDocumentBusinesses(
                $request->owner(),
                $request->term(),
                $id === null ? null : BusinessId::fromInt($id),
                $this->page($request),
            ),
        );

        return $this->options($found, $request);
    }

    public function contacts(DocumentPartySelectionRequest $request): AnonymousResourceCollection
    {
        $id = $request->selectedId();
        // The route names the same use case in its `allowed` middleware: change them together
        $found = $this->useCases->run(
            $this->admin(),
            new ListDocumentContacts(
                $request->owner(),
                $request->term(),
                $id === null ? null : ContactId::fromInt($id),
                $this->page($request),
            ),
        );

        return $this->options($found, $request);
    }

    /**
     * Only active accounts can be newly selected; a document keeps its stored account copy.
     */
    public function accounts(DocumentPartySelectionRequest $request): AnonymousResourceCollection
    {
        $accountOwner = $request->accountOwner();
        if ($accountOwner === null) {
            return DocumentPartyOptionResource::collection([]);
        }

        $owner = $request->owner();
        // The route names the first use case in its `allowed` middleware, the second has the same access
        $accounts = $this->useCases->run(
            $this->admin(),
            new ListDocumentBankAccounts($owner, $accountOwner),
        );
        $default = $this->useCases->run(
            $this->admin(),
            new FindDefaultDocumentBankAccount($owner, $accountOwner),
        );
        $options = [];
        foreach ($accounts as $account) {
            if (!$account->isArchived()) {
                $options[] = DocumentPartyOptionDTO::fromBankAccount(
                    $account,
                    $account->id->value === $default?->value,
                );
            }
        }

        return DocumentPartyOptionResource::collection($options);
    }

    private function page(DocumentPartySelectionRequest $request): PageRequest
    {
        return new PageRequest(
            $request->integer('page', 1),
            self::PER_PAGE,
        );
    }

    /**
     * @param  PaginatedResult<BusinessDTO>|PaginatedResult<ContactDTO>  $found
     */
    private function options(PaginatedResult $found, DocumentPartySelectionRequest $request): AnonymousResourceCollection
    {
        return DocumentPartyOptionResource::collection(
            new LengthAwarePaginator(
                array_map(
                    DocumentPartyOptionDTO::fromParty(...),
                    $found->items(),
                ),
                $found->total(),
                $found->perPage(),
                $found->currentPage(),
                ['path' => $request->url()],
            ),
        );
    }
}

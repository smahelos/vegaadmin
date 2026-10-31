<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\Exceptions\ArchivedReferenceSelectedException;
use App\Domain\Party\Exceptions\ContactIdentityChangedException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\Exceptions\IncompleteDocumentPartyException;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;

final class DocumentCounterpartyAssembler
{
    public function __construct(private readonly ContactReadRepositoryInterface $contacts) {}

    public function assemble(
        UserId $userId,
        ContactId $contactId,
        DocumentPartyDTO $entered,
        ?ContactId $storedContactId,
        ?DocumentPartyDTO $storedParty,
        bool $copyContact,
    ): DocumentPartyDTO {
        $contact = $this->contacts->findByIdForUser($contactId, $userId) ?? throw new ContactNotFoundException($contactId);
        $sameContact = $storedContactId?->equals($contactId) === true;
        if ($contact->isArchived() && ! $sameContact) {
            throw new ArchivedReferenceSelectedException($contactId);
        }
        if ($copyContact && ! $sameContact) {
            $entered = DocumentPartyDTO::fromProfile($contact->profile);
        } elseif (! $entered->hasIdentityOf($contact->profile)) {
            // An address-book correction must not force a change to the document's stored identity.
            if (! $sameContact || $storedParty === null || ! $entered->hasIdentityOf($storedParty->toProfile())) {
                throw new ContactIdentityChangedException($contactId);
            }
        }
        if ($copyContact && (! $entered->hasCompleteAddress() || trim($entered->name) === '')) {
            throw new IncompleteDocumentPartyException;
        }

        return $entered;
    }
}

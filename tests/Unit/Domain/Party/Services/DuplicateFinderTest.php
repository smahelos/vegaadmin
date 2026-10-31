<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Party\Services;

use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\Enums\DuplicateReason;
use App\Domain\Party\Services\DuplicateFinder;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Shared\Geography\Enums\Country;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DuplicateFinderTest extends TestCase
{
    #[Test]
    public function a_czech_company_number_match_blocks_and_comes_first(): void
    {
        $duplicates = (new DuplicateFinder)->find(
            $this->profile(
                'New name',
                Country::CZ,
                '27074358',
                null,
            ),
            [
                $this->contact(1, $this->profile('New name', null, null, null)),
                $this->contact(
                    2,
                    $this->profile(
                        'Old name',
                        Country::CZ,
                        '27074358',
                        null,
                    ),
                    archived: true,
                ),
            ],
        );

        $this->assertSame(DuplicateReason::CZECH_COMPANY_NUMBER, $duplicates[0]->reason);
        $this->assertTrue($duplicates[0]->reason->isBlocking());
        $this->assertSame(2, $duplicates[0]->id->value);
        $this->assertTrue($duplicates[0]->archived);
        $this->assertSame(DuplicateReason::NAME, $duplicates[1]->reason);
        $this->assertFalse($duplicates[1]->reason->isBlocking());
    }

    #[Test]
    public function a_foreign_number_in_the_same_register_only_warns(): void
    {
        $duplicates = (new DuplicateFinder)->find(
            $this->profile(
                'A',
                Country::AT,
                'FN 1',
                null,
            ),
            [
                $this->contact(1, $this->profile('B', Country::AT, 'FN 1', null)),
                $this->contact(2, $this->profile('C', Country::DE, 'FN 1', null)),
            ],
        );

        $this->assertCount(1, $duplicates);
        $this->assertSame(DuplicateReason::COMPANY_NUMBER, $duplicates[0]->reason);
        $this->assertFalse($duplicates[0]->reason->isBlocking());
    }

    #[Test]
    public function the_name_and_email_match_regardless_of_case_and_spaces(): void
    {
        $duplicates = (new DuplicateFinder)->find(
            $this->profile(
                'Acme s.r.o.',
                null,
                null,
                'Office@Acme.test',
            ),
            [
                $this->contact(1, $this->profile(' ACME S.R.O. ', null, null, null)),
                $this->contact(2, $this->profile('Other', null, null, 'office@acme.test')),
                $this->contact(3, $this->profile('Unrelated', null, null, null)),
            ],
        );

        $this->assertSame([DuplicateReason::NAME, DuplicateReason::EMAIL], array_map(fn ($duplicate) => $duplicate->reason, $duplicates));
    }

    private function profile(
        string $name,
        ?Country $country,
        ?string $number,
        ?string $email,
    ): PartyProfile {
        return new PartyProfile(
            name: $name,
            shortName: null,
            registrationNumber: RegistrationNumber::fromParts($country, $number),
            vatNumber: null,
            email: $email,
            phone: null,
            note: null,
            address: null,
        );
    }

    private function contact(
        int $id,
        PartyProfile $profile,
        bool $archived = false,
    ): ContactDTO {
        return new ContactDTO(
            id: ContactId::fromInt($id),
            userId: UserId::fromInt(1),
            profile: $profile,
            contactPerson: null,
            defaultBankAccountId: null,
            archived: $archived ? new DateTimeImmutable('2026-01-01') : null,
        );
    }
}

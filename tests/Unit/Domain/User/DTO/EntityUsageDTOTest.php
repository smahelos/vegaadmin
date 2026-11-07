<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User\DTO;

use App\Domain\User\DTO\EntityUsageDTO;
use App\Domain\User\Enums\LimitedEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EntityUsageDTOTest extends TestCase
{
    /**
     * @return array<string, array{int, ?int, int, bool, bool}>
     */
    public static function usages(): array
    {
        return [
            'below the warning' => [
                7,
                10,
                70,
                false,
                false,
            ],
            'at the warning' => [
                8,
                10,
                80,
                false,
                true,
            ],
            'at the limit' => [
                10,
                10,
                100,
                true,
                false,
            ],
            'above the limit after a role change' => [
                12,
                10,
                100,
                true,
                false,
            ],
            'unlimited' => [
                50,
                null,
                0,
                false,
                false,
            ],
            'not allowed' => [
                3,
                0,
                0,
                false,
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('usages')]
    public function reports_the_share_used_and_the_warning_state(
        int $used,
        ?int $limit,
        int $percent,
        bool $full,
        bool $near,
    ): void {
        $usage = new EntityUsageDTO(
            LimitedEntity::INVOICE,
            $used,
            $limit,
        );

        $this->assertSame($percent, $usage->percent());
        $this->assertSame($full, $usage->full());
        $this->assertSame($near, $usage->near());
    }
}

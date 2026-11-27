<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\ValueObjects\StoredPdf;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StoredPdfTest extends TestCase
{
    #[Test]
    public function matches_only_the_content_it_was_made_of(): void
    {
        $pdf = StoredPdf::of('1/2.pdf', '%PDF-1.4 issued');

        $this->assertSame(
            hash('sha256', '%PDF-1.4 issued'),
            $pdf->hash,
        );
        $this->assertTrue(
            $pdf->matches('%PDF-1.4 issued'),
        );
        $this->assertFalse(
            $pdf->matches('%PDF-1.4 changed'),
        );
    }

    /** @return array<string, array{string, string}> */
    public static function invalid(): array
    {
        $hash = hash('sha256', 'pdf');

        return [
            'empty path' => ['', $hash],
            'short hash' => ['1/2.pdf', substr($hash, 1)],
            'uppercase hash' => ['1/2.pdf', strtoupper($hash)],
        ];
    }

    #[Test]
    #[DataProvider('invalid')]
    public function refuses_an_empty_path_or_a_malformed_hash(string $path, string $hash): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StoredPdf($path, $hash);
    }
}

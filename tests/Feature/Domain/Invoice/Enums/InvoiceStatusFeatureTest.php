<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Invoice\Enums;

use App\Domain\Invoice\Enums\InvoiceStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceStatusFeatureTest extends TestCase
{
    #[Test]
    public function every_invoice_status_has_a_seeded_invoice_status_row(): void
    {
        $slugs = DB::table('statuses')
            ->join(
                'status_categories',
                'statuses.category_id',
                '=',
                'status_categories.id',
            )
            ->where('status_categories.slug', 'invoice-statuses')
            ->pluck('statuses.slug')
            ->all();

        $expected = array_map(static fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::cases());
        sort($expected);
        sort($slugs);

        $this->assertSame($expected, $slugs);
    }

    #[Test]
    public function every_invoice_status_has_a_label_in_every_locale(): void
    {
        foreach (config('app.available_locales') as $locale) {
            foreach (InvoiceStatus::cases() as $status) {
                $this->assertTrue(
                    Lang::hasForLocale('statuses.statuses.' . $status->value, $locale),
                    $status->value . ' has no label in ' . $locale,
                );
            }
        }
    }
}

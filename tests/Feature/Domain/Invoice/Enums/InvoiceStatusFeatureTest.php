<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Invoice\Enums;

use App\Domain\Invoice\Enums\InvoiceStatus;
use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceStatusFeatureTest extends TestCase
{
    #[Test]
    public function every_invoice_status_has_a_label_in_every_locale(): void
    {
        foreach (config('app.available_locales') as $locale) {
            foreach (InvoiceStatus::cases() as $status) {
                $this->assertTrue(
                    Lang::hasForLocale('invoices.statuses.' . $status->value, $locale),
                    $status->value . ' has no label in ' . $locale,
                );
            }
        }
    }
}

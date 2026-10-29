<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Invoice\Services\InvoiceReminderApplicationService;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

final class TestInvoiceReminder extends Command
{
    protected $signature = 'invoices:test-reminder {invoice_id? : Invoice ID to test} {--invoice= : Invoice ID to test (alternative option)} {--type=all : Reminder type (upcoming, due, overdue, all)}';

    protected $description = 'Invoice reminder test for selected invoice';

    public function handle(InvoiceReminderApplicationService $reminders): int
    {
        $type = $this->option('type');
        $types = match ($type) {
            'upcoming' => [InvoiceReminderType::UPCOMING_DUE],
            'due' => [InvoiceReminderType::DUE_TODAY],
            'overdue' => [InvoiceReminderType::OVERDUE],
            'all' => InvoiceReminderType::cases(),
            default => null,
        };
        if ($types === null) {
            $this->error('Invalid reminder type. Valid types: upcoming, due, overdue, all.');

            return self::FAILURE;
        }
        $id = $this->argument('invoice_id') ?: $this->option('invoice');
        if ($id === null) {
            $this->info("Testing {$type} reminder functionality without specific invoice. No notification was sent.");

            return self::SUCCESS;
        }
        $id = filter_var(
            $id,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );
        if ($id === false) {
            $this->error('Invoice ID must be a positive integer.');

            return self::FAILURE;
        }
        try {
            $reminders->sendForInvoice(InvoiceId::fromInt($id), $types);
            $this->info('Testing finished.');
        } catch (Throwable $exception) {
            Log::error('Invoice test reminder failed', ['exception' => $exception]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

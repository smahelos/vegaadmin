<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Invoice\Services\InvoiceReminderApplicationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CheckInvoicePaymentStatus extends Command
{
    protected $signature = 'invoices:check-payment-status {--days-before=3 : Days before due date to send upcoming reminder} {--days-after=1 : Days after due date to send overdue reminder}';

    protected $description = 'Check invoice payment status and send reminders based on due dates';

    public function handle(InvoiceReminderApplicationService $reminders): int
    {
        $this->info('Checking invoice for payment reminder...');
        $daysBefore = filter_var(
            $this->option('days-before'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );
        $daysAfter = filter_var(
            $this->option('days-after'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );
        if ($daysBefore === false || $daysAfter === false) {
            $this->error('Reminder offsets must be positive integers.');

            return self::INVALID;
        }
        try {
            $result = $reminders->sendReminders($daysBefore, $daysAfter);
            $this->info("Notifications sent: {$result->upcoming} upcoming, {$result->dueToday} today, {$result->overdue} overdue.");
        } catch (Throwable $exception) {
            $this->error('Error while checking invoices: ' . $exception->getMessage());
            Log::error('Invoice reminder check failed', ['exception' => $exception]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

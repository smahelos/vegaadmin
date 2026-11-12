<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Invoice\ValueObjects\InvoiceId;

/**
 * An operator's test reminder for one invoice from the console: shell access to the server is the trust
 * boundary, so any owner's invoice can be named.
 *
 * @implements CommandInterface<null>
 */
final readonly class SendInvoiceReminder implements CommandInterface
{
    /**
     * @param  list<InvoiceReminderType>  $types
     */
    public function __construct(
        public InvoiceId $invoiceId,
        public array $types,
    ) {}

    public static function access(): Access
    {
        return Access::system();
    }
}

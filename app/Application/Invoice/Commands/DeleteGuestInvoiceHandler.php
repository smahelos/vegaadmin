<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;

final class DeleteGuestInvoiceHandler
{
    public function __construct(
        private readonly TemporaryInvoiceStoreInterface $store,
    ) {}

    public function handle(DeleteGuestInvoice $command): void
    {
        if ($command->sessionToken !== $command->token) {
            throw new InvoiceNotFoundException('Guest invoice of another session');
        }

        $this->store->delete($command->token);
    }
}

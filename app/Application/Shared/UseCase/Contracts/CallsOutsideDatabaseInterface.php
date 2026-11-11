<?php

declare(strict_types=1);

namespace App\Application\Shared\UseCase\Contracts;

/**
 * A Command that calls outside the database (payment gateway, e-mail, files). The gate opens no transaction
 * for it; its handler opens one through TransactionBoundaryInterface around its writes only.
 */
interface CallsOutsideDatabaseInterface {}

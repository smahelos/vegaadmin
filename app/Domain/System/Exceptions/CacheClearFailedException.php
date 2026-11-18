<?php

declare(strict_types=1);

namespace App\Domain\System\Exceptions;

use RuntimeException;

/**
 * A cache could not be cleared.
 */
final class CacheClearFailedException extends RuntimeException {}

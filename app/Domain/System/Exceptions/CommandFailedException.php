<?php

declare(strict_types=1);

namespace App\Domain\System\Exceptions;

use RuntimeException;

/**
 * A command threw while it ran; the original exception is the previous one.
 */
final class CommandFailedException extends RuntimeException {}

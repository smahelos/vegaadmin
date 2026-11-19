<?php

declare(strict_types=1);

namespace App\Domain\System\Exceptions;

use RuntimeException;

/**
 * A cron task run failed; its output is stored with the task.
 */
final class CronTaskFailedException extends RuntimeException {}

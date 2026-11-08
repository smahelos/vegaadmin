<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Domain\User\ValueObjects\UserId;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The creation rate limit of an action. Livewire actions do not pass route middleware, so they count against the
 * bucket of the `throttle:create` middleware by its unhashed key (RouteServiceProvider).
 */
trait ThrottlesCreation
{
    abstract protected function userId(): UserId;

    /**
     * Counts one creation; the message to show once the limit is used up, null when the creation may go on.
     */
    protected function creationThrottled(): ?string
    {
        $userId = $this->userId();
        $key = 'create:' . $userId->toInt();
        if (
            RateLimiter::tooManyAttempts(
                $key,
                Config::integer('billex.rate_limits.create'),
            )
        ) {
            return __(
                'general.messages.too_many_requests',
                ['seconds' => RateLimiter::availableIn($key)],
            );
        }

        RateLimiter::hit($key);

        return null;
    }
}

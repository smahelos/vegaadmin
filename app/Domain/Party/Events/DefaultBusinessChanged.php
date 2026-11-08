<?php

declare(strict_types=1);

namespace App\Domain\Party\Events;

/**
 * The user's default business changed or, with the last active business archived, is gone.
 */
final readonly class DefaultBusinessChanged extends PartyEvent {}

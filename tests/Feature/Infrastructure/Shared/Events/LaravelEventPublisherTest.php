<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Shared\Events;

use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Events\FormDataChanged;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class LaravelEventPublisherTest extends TestCase
{
    #[Test]
    public function it_delivers_an_event_at_once_outside_a_transaction(): void
    {
        Event::fake([FormDataChanged::class]);

        $this->publisher()->publish(new FormDataChanged(1, 'products'));

        Event::assertDispatchedTimes(FormDataChanged::class, 1);
    }

    #[Test]
    public function it_delivers_an_event_only_after_the_transaction_commits(): void
    {
        Event::fake([FormDataChanged::class]);

        $this->transactions()->transaction(function (): void {
            $this->publisher()->publish(new FormDataChanged(1, 'products'));

            Event::assertNotDispatched(FormDataChanged::class);
        });

        Event::assertDispatchedTimes(FormDataChanged::class, 1);
    }

    #[Test]
    public function a_rolled_back_transaction_delivers_nothing(): void
    {
        Event::fake([FormDataChanged::class]);

        try {
            $this->transactions()->transaction(function (): void {
                $this->publisher()->publish(new FormDataChanged(1, 'products'));

                throw new RuntimeException('Use case failed');
            });
        } catch (RuntimeException) {
        }

        Event::assertNotDispatched(FormDataChanged::class);
    }

    #[Test]
    public function a_rolled_back_nested_transaction_delivers_only_the_events_of_the_outer_one(): void
    {
        Event::fake([FormDataChanged::class]);

        $this->transactions()->transaction(function (): void {
            $this->publisher()->publish(new FormDataChanged(1, 'outer'));

            try {
                $this->transactions()->transaction(function (): void {
                    $this->publisher()->publish(new FormDataChanged(1, 'inner'));

                    throw new RuntimeException('Inner step failed');
                });
            } catch (RuntimeException) {
            }
        });

        Event::assertDispatchedTimes(FormDataChanged::class, 1);
        Event::assertDispatched(FormDataChanged::class, fn (FormDataChanged $event): bool => $event->changeType === 'outer');
    }

    private function publisher(): EventPublisherInterface
    {
        return $this->app->make(EventPublisherInterface::class);
    }

    private function transactions(): TransactionBoundaryInterface
    {
        return $this->app->make(TransactionBoundaryInterface::class);
    }
}

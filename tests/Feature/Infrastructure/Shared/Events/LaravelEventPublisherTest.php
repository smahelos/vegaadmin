<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Shared\Events;

use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Events\DomainEventDispatcher;
use App\Domain\Shared\Events\DomainEventHandler;
use App\Domain\Shared\Events\FormDataChanged;
use App\Domain\Shared\Log\Contracts\LogInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\Fakes\Shared\RecordingLog;
use Tests\TestCase;

final class LaravelEventPublisherTest extends TestCase
{
    #[Test]
    public function listeners_receive_an_event_at_once_outside_a_transaction(): void
    {
        Event::fake([FormDataChanged::class]);

        $publisher = $this->publisher();
        $publisher->publish(
            new FormDataChanged(1, 'products'),
        );

        Event::assertDispatchedTimes(FormDataChanged::class, 1);
    }

    #[Test]
    public function listeners_receive_an_event_only_after_the_transaction_commits(): void
    {
        Event::fake([FormDataChanged::class]);

        $transactions = $this->transactions();
        $transactions->transaction(
            function (): void {
                $publisher = $this->publisher();
                $publisher->publish(
                    new FormDataChanged(1, 'products'),
                );

                Event::assertNotDispatched(FormDataChanged::class);
            },
        );

        Event::assertDispatchedTimes(FormDataChanged::class, 1);
    }

    #[Test]
    public function listeners_receive_nothing_from_a_rolled_back_transaction(): void
    {
        Event::fake([FormDataChanged::class]);

        try {
            $transactions = $this->transactions();
            $transactions->transaction(
                function (): void {
                    $publisher = $this->publisher();
                    $publisher->publish(
                        new FormDataChanged(1, 'products'),
                    );

                    throw new RuntimeException('Use case failed');
                },
            );
        } catch (RuntimeException) {
        }

        Event::assertNotDispatched(FormDataChanged::class);
    }

    #[Test]
    public function listeners_receive_only_the_events_of_the_outer_transaction_when_a_nested_one_rolls_back(): void
    {
        Event::fake([FormDataChanged::class]);

        $transactions = $this->transactions();
        $transactions->transaction(
            function (): void {
                $publisher = $this->publisher();
                $publisher->publish(
                    new FormDataChanged(1, 'outer'),
                );

                try {
                    $transactions = $this->transactions();
                    $transactions->transaction(
                        function (): void {
                            $publisher = $this->publisher();
                            $publisher->publish(
                                new FormDataChanged(1, 'inner'),
                            );

                            throw new RuntimeException('Inner step failed');
                        },
                    );
                } catch (RuntimeException) {
                }
            },
        );

        Event::assertDispatchedTimes(FormDataChanged::class, 1);
        Event::assertDispatched(
            FormDataChanged::class,
            fn (FormDataChanged $event): bool => $event->changeType === 'outer',
        );
    }

    #[Test]
    public function a_failing_listener_is_logged_and_leaves_the_committed_use_case_and_the_domain_handlers_alone(): void
    {
        $log = new RecordingLog;
        $this->app->instance(LogInterface::class, $log);
        Event::listen(
            FormDataChanged::class,
            function (): void {
                throw new RuntimeException('Cache is down');
            },
        );
        $handler = $this->recordingHandler();
        $dispatcher = $this->app->make(DomainEventDispatcher::class);
        $dispatcher->register(FormDataChanged::class, $handler);

        $transactions = $this->transactions();
        $result = $transactions->transaction(
            function (): string {
                $publisher = $this->publisher();
                $publisher->publish(
                    new FormDataChanged(1, 'products'),
                );

                return 'committed';
            },
        );

        $this->assertSame('committed', $result);
        $this->assertCount(1, $handler->transactionLevels);
        $this->assertCount(1, $log->entries);
        $this->assertSame('error', $log->entries[0]['level']);
        $this->assertSame('Domain event listener failed', $log->entries[0]['message']);
        $this->assertInstanceOf(RuntimeException::class, $log->entries[0]['context']['exception']);
    }

    #[Test]
    public function domain_handlers_run_inside_the_transaction(): void
    {
        $handler = $this->recordingHandler();
        $dispatcher = $this->app->make(DomainEventDispatcher::class);
        $dispatcher->register(FormDataChanged::class, $handler);
        $levelOutside = DB::transactionLevel();

        $transactions = $this->transactions();
        $transactions->transaction(
            function (): void {
                $publisher = $this->publisher();
                $publisher->publish(
                    new FormDataChanged(1, 'products'),
                );
            },
        );

        $this->assertSame([$levelOutside + 1], $handler->transactionLevels);
    }

    /**
     * A domain handler that records the transaction level of every event it handles.
     *
     * @return DomainEventHandler&object{transactionLevels: list<int>}
     */
    private function recordingHandler(): DomainEventHandler
    {
        return new class implements DomainEventHandler
        {
            /** @var list<int> */
            public array $transactionLevels = [];

            public function handle(DomainEvent $event): void
            {
                $this->transactionLevels[] = DB::transactionLevel();
            }

            public function canHandle(DomainEvent $event): bool
            {
                return true;
            }

            public function getPriority(): int
            {
                return 0;
            }
        };
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

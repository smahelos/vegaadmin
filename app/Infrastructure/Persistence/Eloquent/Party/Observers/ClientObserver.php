<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Observers;

use App\Models\Client;
use App\Domain\User\Events\UserDataChanged;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;

class ClientObserver
{
    public function created(Client $client): void
    {
        if ($client->user_id) {
            app(EventPublisherInterface::class)->publish(new UserDataChanged((int)$client->user_id, 'client'));
        }
    }
    
    public function updated(Client $client): void
    {
        if ($client->isDirty(['name', 'email'])) {
            if ($client->user_id) {
                app(EventPublisherInterface::class)->publish(new UserDataChanged((int)$client->user_id, 'client'));
            }
        }
    }
    
    public function deleted(Client $client): void
    {
        if ($client->user_id) {
            app(EventPublisherInterface::class)->publish(new UserDataChanged((int)$client->user_id, 'client'));
        }
    }
}

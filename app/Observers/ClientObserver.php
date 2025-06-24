<?php

namespace App\Observers;

use App\Models\Client;
use App\Events\UserDataChanged;

class ClientObserver
{
    /**
     * Handle the Client "created" event.
     */
    public function created(Client $client): void
    {
        // Fire cache invalidation event for client-related dashboard stats
        if ($client->user) {
            UserDataChanged::dispatch($client->user, 'client');
        }
    }
    
    /**
     * Handle the Client "updated" event.
     */
    public function updated(Client $client): void
    {
        // Fire cache invalidation event if relevant fields changed
        if ($client->isDirty(['name', 'email'])) {
            if ($client->user) {
                UserDataChanged::dispatch($client->user, 'client');
            }
        }
    }
    
    /**
     * Handle the Client "deleted" event.
     */
    public function deleted(Client $client): void
    {
        // Fire cache invalidation event for client-related stats
        if ($client->user) {
            UserDataChanged::dispatch($client->user, 'client');
        }
    }
}

<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Models\Client;

class EloquentClientRepository
{
    public function findById(int $id): ?Client
    {
        return Client::find($id); // Caller must enforce ownership if needed
    }
}

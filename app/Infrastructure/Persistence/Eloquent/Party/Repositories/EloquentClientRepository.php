<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Application\Party\Contracts\ClientReadRepositoryInterface;
use App\Application\Party\Contracts\ClientWriteRepositoryInterface;
use App\Models\Client;

class EloquentClientRepository implements ClientReadRepositoryInterface, ClientWriteRepositoryInterface
{
    public function getClientsForDropdown(int $userId): array
    {
        return Client::where('user_id', $userId)->pluck('name','id')->toArray();
    }

    public function getDefaultClient(int $userId): ?Client
    {
        return Client::where('user_id',$userId)->where('is_default',true)->first();
    }

    public function findById(int $id): ?Client
    {
        return Client::find($id); // Caller must enforce ownership if needed
    }

    public function findByIdAny(int $id): ?Client
    {
        return Client::find($id);
    }

    public function create(array $data): Client
    {
        return Client::create($data);
    }

    public function unsetOthersDefault(int $currentId, int $userId): void
    {
        Client::where('user_id',$userId)->where('id','!=',$currentId)->update(['is_default'=>false]);
    }

    public function allForAdmin(): array
    {
        return Client::all()->toArray();
    }

    public function allForUser(int $userId): array
    {
        return Client::where('user_id',$userId)->get()->toArray();
    }

    public function allModelsForAdmin(): \Illuminate\Support\Collection
    {
        return Client::all();
    }

    public function allModelsForUser(int $userId): \Illuminate\Support\Collection
    {
        return Client::where('user_id',$userId)->get();
    }

    public function firstForUser(int $userId): ?Client
    {
        return Client::where('user_id',$userId)->orderByDesc('created_at')->first();
    }

    public function findByIdForUser(int $id, int $userId): ?Client
    {
        return Client::where('id',$id)->where('user_id',$userId)->first();
    }

    public function updateById(int $id, array $data): bool
    {
        $client = Client::find($id);
        if (!$client) { return false; }
        return (bool)$client->update($data);
    }

    public function deleteById(int $id): bool
    {
        $client = Client::find($id);
        if (!$client) { return false; }
        return (bool)$client->delete();
    }

    public function hasLinkedInvoices(int $id): bool
    {
        $client = Client::find($id);
        if (!$client) { return false; }
        return $client->invoices()->exists();
    }
}

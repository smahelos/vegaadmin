<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DocumentPartySelectionRequest;
use App\Http\Resources\Admin\DocumentPartyOptionResource;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentDocumentPartyOptionMapper;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class DocumentPartyCrudController extends CrudController
{
    public function businesses(DocumentPartySelectionRequest $request): AnonymousResourceCollection
    {

        return DocumentPartyOptionResource::collection(Business::query()
            ->where('user_id', $request->integer('user_id'))
            ->when($request->filled('id'), fn ($query) => $query->whereKey($request->integer('id')))
            ->whereNull('archived_at')
            ->where(
                'name',
                'like',
                '%' . $request->string('q')->toString() . '%',
            )
            ->with('address')->orderBy('name')
            ->paginate(25)->through(EloquentDocumentPartyOptionMapper::toDTO(...)));
    }

    public function contacts(DocumentPartySelectionRequest $request): AnonymousResourceCollection
    {

        return DocumentPartyOptionResource::collection(Contact::query()
            ->where('user_id', $request->integer('user_id'))
            ->when($request->filled('id'), fn ($query) => $query->whereKey($request->integer('id')))
            ->whereNull('archived_at')
            ->where(
                'name',
                'like',
                '%' . $request->string('q')->toString() . '%',
            )
            ->with('address')->orderBy('name')
            ->paginate(25)->through(EloquentDocumentPartyOptionMapper::toDTO(...)));
    }

    public function accounts(DocumentPartySelectionRequest $request): AnonymousResourceCollection
    {
        $businessId = $request->integer('business_id');
        $contactId = $request->integer('contact_id');
        $query = BankAccount::query()->whereNull('archived_at');
        if ($businessId > 0) {
            $query->where('business_id', $businessId)
                ->whereHas('business', fn ($owner) => $owner->where('user_id', $request->integer('user_id'))->whereNull('archived_at'));
        } elseif ($contactId > 0) {
            $query->where('contact_id', $contactId)
                ->whereHas('contact', fn ($owner) => $owner->where('user_id', $request->integer('user_id'))->whereNull('archived_at'));
        } else {
            $query->whereRaw('1 = 0');
        }

        return DocumentPartyOptionResource::collection($query->with(['business', 'contact'])->orderBy('id')->get()->map(EloquentDocumentPartyOptionMapper::toDTO(...)));
    }
}

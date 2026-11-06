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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class DocumentPartyCrudController extends CrudController
{
    public function businesses(DocumentPartySelectionRequest $request): AnonymousResourceCollection
    {
        $query = Business::query();
        $query->where(
            'user_id',
            $request->integer('user_id'),
        );
        $query->when(
            $request->filled('id'),
            fn ($query) => $query->whereKey(
                $request->integer('id'),
            ),
        );
        $query->whereNull('archived_at');
        $search = $request->string('q');
        $query->where(
            'name',
            'like',
            '%' . $search->toString() . '%',
        );
        $query->with('address');
        $query->orderBy('name');
        $page = $query->paginate(25);

        return DocumentPartyOptionResource::collection(
            $page->through(
                EloquentDocumentPartyOptionMapper::toDTO(...),
            ),
        );
    }

    public function contacts(DocumentPartySelectionRequest $request): AnonymousResourceCollection
    {
        $query = Contact::query();
        $query->where(
            'user_id',
            $request->integer('user_id'),
        );
        $query->when(
            $request->filled('id'),
            fn ($query) => $query->whereKey(
                $request->integer('id'),
            ),
        );
        $query->whereNull('archived_at');
        $search = $request->string('q');
        $query->where(
            'name',
            'like',
            '%' . $search->toString() . '%',
        );
        $query->with('address');
        $query->orderBy('name');
        $page = $query->paginate(25);

        return DocumentPartyOptionResource::collection(
            $page->through(
                EloquentDocumentPartyOptionMapper::toDTO(...),
            ),
        );
    }

    public function accounts(DocumentPartySelectionRequest $request): AnonymousResourceCollection
    {
        $businessId = $request->integer('business_id');
        $contactId = $request->integer('contact_id');
        $query = BankAccount::query();
        $query->whereNull('archived_at');
        if ($businessId > 0) {
            $query->where('business_id', $businessId);
            $query->whereHas(
                'business',
                function (Builder $owner) use ($request): void {
                    $owner->where(
                        'user_id',
                        $request->integer('user_id'),
                    );
                    $owner->whereNull('archived_at');
                },
            );
        } elseif ($contactId > 0) {
            $query->where('contact_id', $contactId);
            $query->whereHas(
                'contact',
                function (Builder $owner) use ($request): void {
                    $owner->where(
                        'user_id',
                        $request->integer('user_id'),
                    );
                    $owner->whereNull('archived_at');
                },
            );
        } else {
            $query->whereRaw('1 = 0');
        }

        $query->with(['business', 'contact']);
        $query->orderBy('id');
        $accounts = $query->get();

        return DocumentPartyOptionResource::collection(
            $accounts->map(
                EloquentDocumentPartyOptionMapper::toDTO(...),
            ),
        );
    }
}

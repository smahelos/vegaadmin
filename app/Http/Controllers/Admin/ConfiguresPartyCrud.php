<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Forms\Party\PartyFormFields;
use App\Models\Address;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\Facades\Alert;

/**
 * The admin screens businesses and contacts share: access by permission, list columns and filters, the owner and
 * profile fields, and archiving and restoring. Records are written through the application services with the
 * record's owner, so the owner's limits and invariants apply; there is no delete.
 */
trait ConfiguresPartyCrud
{
    private const STATUS_ACTIVE = 'active';

    private const STATUS_ARCHIVED = 'archived';

    /**
     * @param  class-string<Business|Contact>  $model
     * @param  'business'|'contact'  $entity
     */
    protected function setupParty(
        string $model,
        string $entity,
        string $translations,
    ): void {
        CRUD::setModel($model);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/' . $entity);
        CRUD::setEntityNameStrings(
            __($translations . '.entity.singular'),
            __($translations . '.entity.plural'),
        );

        [$view, $create, $update] = match ($entity) {
            'business' => [
                'can_view_business',
                'can_create_business',
                'can_update_business',
            ],
            'contact' => [
                'can_view_contact',
                'can_create_contact',
                'can_update_contact',
            ],
        };
        $user = backpack_user();
        $denied = [];
        if ($user?->can($view) !== true) {
            $denied[] = 'list';
            $denied[] = 'show';
        }

        if ($user?->can($create) !== true) {
            $denied[] = 'create';
        }

        if ($user?->can($update) !== true) {
            $denied[] = 'update';
        }

        CRUD::denyAccess($denied);
    }

    protected function setupPartyList(string $entity): void
    {
        CRUD::column('user')
            ->label(__('parties.fields.owner'))
            ->type('relationship')
            ->attribute('name');
        CRUD::column('name')->label(__('parties.fields.name'));
        CRUD::column('company_number')->label(__('parties.fields.company_number'));
        CRUD::column('email')->label(__('parties.fields.email'));
        CRUD::column('city')
            ->label(__('parties.fields.city'))
            ->type('closure')
            ->function(fn (Business|Contact $entry): string => e($entry->address?->city ?? ''))
            ->escaped(false);
        CRUD::column('archived_at')
            ->label(__('parties.statuses.archived'))
            ->type('datetime');

        CRUD::filter('user_id')
            ->type('select2')
            ->label(__('parties.fields.owner'))
            ->values(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
            ->whenActive(function (string $value): void {
                CRUD::addClause(
                    'where',
                    'user_id',
                    (int) $value,
                );
            });
        CRUD::filter('status')
            ->type('select2')
            ->label(__('parties.filters.status'))
            ->values([
                self::STATUS_ACTIVE => __('parties.filters.active'),
                self::STATUS_ARCHIVED => __('parties.filters.archived'),
            ])
            ->whenActive(function (string $value): void {
                CRUD::addClause(fn (Builder $query): Builder => $value === self::STATUS_ARCHIVED
                    ? $query->whereNotNull('archived_at')
                    : $query->whereNull('archived_at'));
            });

        CRUD::addButton(
            'line',
            'party_bank_accounts',
            'view',
            'admin.buttons.party_bank_accounts',
            'end',
        );
        CRUD::addButton(
            'line',
            'party_archive',
            'view',
            'admin.buttons.party_archive',
            'end',
        );
        CRUD::set('party.entity', $entity);
    }

    protected function addOwnerField(): void
    {
        CRUD::field('user_id')
            ->label(__('parties.fields.owner'))
            ->type('select2_from_array')
            ->options(User::query()->orderBy('name')->pluck('name', 'id')->all())
            ->allows_null(false);
    }

    /**
     * The profile fields, prefilled with the address of the record being edited.
     */
    protected function addProfileFields(?Address $address = null): void
    {
        $countries = app(CountryOptionsPresenter::class)->options(app()->getLocale());

        CRUD::addFields(PartyFormFields::profile($countries, [
            'street' => $address?->street,
            'city' => $address?->city,
            'postal_code' => $address?->postal_code,
            'country' => $address?->country,
        ]));
    }

    protected function ownerOf(Business|Contact $entry): UserId
    {
        return UserId::fromInt($entry->user_id);
    }

    protected function redirectWithSuccess(string $message): RedirectResponse
    {
        Alert::success($message)->flash();

        return redirect()->back();
    }
}

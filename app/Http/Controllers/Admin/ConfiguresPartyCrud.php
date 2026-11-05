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
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/' . $entity,
        );
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
        $column = CRUD::column('user');
        $column->label(
            __('parties.fields.owner'),
        );
        $column->type('relationship');
        $column->attribute('name');
        $column = CRUD::column('name');
        $column->label(
            __('parties.fields.name'),
        );
        $column = CRUD::column('company_number');
        $column->label(
            __('parties.fields.company_number'),
        );
        $column = CRUD::column('email');
        $column->label(
            __('parties.fields.email'),
        );
        $column = CRUD::column('city');
        $column->label(
            __('parties.fields.city'),
        );
        $column->type('closure');
        $column->function(
            fn (Business|Contact $entry): string => e($entry->address?->city ?? ''),
        );
        $column->escaped(false);
        $column = CRUD::column('archived_at');
        $column->label(
            __('parties.statuses.archived'),
        );
        $column->type('datetime');

        $userQuery = User::query();
        $userQuery->orderBy('name');
        $userNames = $userQuery->pluck('name', 'id');

        $filter = CRUD::filter('user_id');
        $filter->type('select2');
        $filter->label(
            __('parties.fields.owner'),
        );
        $filter->values($userNames->all());
        $filter->whenActive(
            function (string $value): void {
                CRUD::addClause(
                    'where',
                    'user_id',
                    (int) $value,
                );
            },
        );
        $filter = CRUD::filter('status');
        $filter->type('select2');
        $filter->label(
            __('parties.filters.status'),
        );
        $filter->values(
            [
                self::STATUS_ACTIVE => __('parties.filters.active'),
                self::STATUS_ARCHIVED => __('parties.filters.archived'),
            ],
        );
        $filter->whenActive(
            function (string $value): void {
                CRUD::addClause(
                    fn (Builder $query): Builder => $value === self::STATUS_ARCHIVED
                    ? $query->whereNotNull('archived_at')
                    : $query->whereNull('archived_at'),
                );
            },
        );

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
        $field = CRUD::field('user_id');
        $field->label(
            __('parties.fields.owner'),
        );
        $field->type('select2_from_array');
        $userQuery = User::query();
        $userQuery->orderBy('name');
        $userNames = $userQuery->pluck('name', 'id');
        $field->options($userNames->all());
        $field->allows_null(false);
    }

    /**
     * The profile fields, prefilled with the address of the record being edited.
     */
    protected function addProfileFields(?Address $address = null): void
    {
        $application = app();
        $presenter = app(CountryOptionsPresenter::class);
        $countries = $presenter->options($application->getLocale());

        CRUD::addFields(
            PartyFormFields::profile(
                $countries,
                [
                    'street' => $address?->street,
                    'city' => $address?->city,
                    'postal_code' => $address?->postal_code,
                    'country' => $address?->country,
                ],
            ),
        );
    }

    protected function ownerOf(Business|Contact $entry): UserId
    {
        return UserId::fromInt($entry->user_id);
    }

    protected function redirectWithSuccess(string $message): RedirectResponse
    {
        $alerts = Alert::success($message);
        $alerts->flash();
        $redirector = redirect();

        return $redirector->back();
    }
}

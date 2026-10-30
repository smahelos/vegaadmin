<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics\Queries;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

final class CustomerRevenueQuery
{
    /**
     * The name of a linked customer comes from the contact, including archived contacts. A one-off
     * customer's registration identity wins over its display name; unnamed identities group by name.
     *
     * @param  Builder<Invoice>  $invoices
     * @return Builder<Invoice>
     */
    public static function group(Builder $invoices): Builder
    {
        $identity = "CASE WHEN invoices.contact_id IS NOT NULL THEN NULL WHEN NULLIF(invoices.customer_company_number, '') IS NOT NULL THEN invoices.customer_company_number ELSE invoices.customer_name END";
        $country = "CASE WHEN invoices.contact_id IS NULL AND NULLIF(invoices.customer_company_number, '') IS NOT NULL THEN invoices.customer_registration_country ELSE NULL END";
        $kind = "CASE WHEN invoices.contact_id IS NOT NULL THEN 'contact' WHEN NULLIF(invoices.customer_company_number, '') IS NOT NULL THEN 'registration' ELSE 'name' END";

        return $invoices
            ->leftJoin('contacts', function (JoinClause $join): void {
                $join->on(
                    'contacts.id',
                    '=',
                    'invoices.contact_id',
                )
                    ->on(
                        'contacts.user_id',
                        '=',
                        'invoices.user_id',
                    );
            })
            ->select('invoices.contact_id')
            ->selectRaw('COALESCE(MAX(contacts.name), MIN(invoices.customer_name)) as customer_name')
            ->selectRaw('SUM(invoices.payment_amount) as total')
            ->groupBy('invoices.contact_id')
            ->groupByRaw($kind)
            ->groupByRaw($identity)
            ->groupByRaw($country);
    }
}

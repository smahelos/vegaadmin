<?php

declare(strict_types=1);

namespace App\Domain\Product\Enums;

/**
 * What a list of products can be ordered by; the value is the sort key in the URL.
 */
enum ProductSort: string
{
    case NAME = 'name';
    case CATEGORY = 'category_id';
    case PRICE = 'price';
    case CURRENCY = 'currency';
    case TAX = 'tax_id';
    case DEFAULT = 'is_default';
    case INVOICES = 'invoices';
    case CREATED = 'created_at';
}

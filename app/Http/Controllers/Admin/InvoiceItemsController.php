<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Invoice\Services\InvoiceItemsApplicationService;
use App\Application\Invoice\Services\InvoiceProductWriteDataAssembler;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceItemsRequest;
use App\Http\Resources\Admin\CatalogProductResource;
use App\Http\Resources\Admin\InvoiceTotalsResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class InvoiceItemsController extends Controller
{
    public function __construct(
        private readonly InvoiceItemsApplicationService $items,
        private readonly InvoiceProductWriteDataAssembler $assembler,
    ) {}

    public function catalog(InvoiceItemsRequest $request): AnonymousResourceCollection
    {
        return CatalogProductResource::collection($this->items->catalog(
            UserId::fromInt((int) backpack_user()->id),
            UserId::fromInt($request->integer('user_id')),
            Currency::from($request->validated('payment_currency')),
            $request->string('q')->toString(),
        ));
    }

    public function preview(InvoiceItemsRequest $request): InvoiceTotalsResource
    {
        return new InvoiceTotalsResource($this->items->preview(
            UserId::fromInt((int) backpack_user()->id),
            UserId::fromInt($request->integer('user_id')),
            Currency::from($request->validated('payment_currency')),
            $this->assembler->fromValidated($request->validated('invoice-products', [])),
        ));
    }
}

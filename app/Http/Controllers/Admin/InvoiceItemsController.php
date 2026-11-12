<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Invoice\Queries\ListInvoiceCatalogProducts;
use App\Application\Invoice\Queries\PreviewInvoiceTotals;
use App\Application\Invoice\Services\InvoiceProductWriteDataAssembler;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceItemsRequest;
use App\Http\Resources\Admin\CatalogProductResource;
use App\Http\Resources\Admin\InvoiceTotalsResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class InvoiceItemsController extends Controller
{
    use ActsAsAdmin;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
        private readonly InvoiceProductWriteDataAssembler $assembler,
    ) {}

    public function catalog(InvoiceItemsRequest $request): AnonymousResourceCollection
    {
        $search = $request->string('q');
        // The route names the same use case in its `allowed` middleware: change them together
        $products = $this->useCases->run(
            $this->admin(),
            new ListInvoiceCatalogProducts(
                $request->owner(),
                $request->currency(),
                $search->toString(),
            ),
        );

        return CatalogProductResource::collection($products);
    }

    public function preview(InvoiceItemsRequest $request): InvoiceTotalsResource
    {
        // The route names the same use case in its `allowed` middleware: change them together
        $totals = $this->useCases->run(
            $this->admin(),
            new PreviewInvoiceTotals(
                $request->owner(),
                $request->currency(),
                $this->assembler->fromValidated(
                    $request->validated('invoice-products', []),
                ),
            ),
        );

        return new InvoiceTotalsResource($totals);
    }
}

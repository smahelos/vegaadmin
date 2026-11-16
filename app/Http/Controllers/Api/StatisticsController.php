<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Analytics\Queries\GetCustomerRevenue;
use App\Application\Analytics\Queries\GetInvoiceStatus;
use App\Application\Analytics\Queries\GetMonthlyRevenue;
use App\Application\Analytics\Queries\GetPaymentMethods;
use App\Application\Analytics\Queries\GetRevenueExpenses;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\StatisticsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

// Each action's route names the same Query in its `allowed` middleware: change them together
final class StatisticsController extends Controller
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    public function monthlyRevenue(StatisticsRequest $request): JsonResponse
    {
        $owner = $this->owner();
        $rows = $this->useCases->run(
            Actor::user($owner),
            new GetMonthlyRevenue($owner, $request->filter()),
        );

        return $this->json($rows);
    }

    public function customerRevenue(StatisticsRequest $request): JsonResponse
    {
        $owner = $this->owner();
        $rows = $this->useCases->run(
            Actor::user($owner),
            new GetCustomerRevenue($owner, $request->filter()),
        );

        return $this->json($rows);
    }

    public function invoiceStatus(StatisticsRequest $request): JsonResponse
    {
        $owner = $this->owner();
        $rows = $this->useCases->run(
            Actor::user($owner),
            new GetInvoiceStatus($owner, $request->filter()),
        );

        return $this->json($rows);
    }

    public function paymentMethods(StatisticsRequest $request): JsonResponse
    {
        $owner = $this->owner();
        $rows = $this->useCases->run(
            Actor::user($owner),
            new GetPaymentMethods($owner, $request->filter()),
        );

        return $this->json($rows);
    }

    public function revenueExpenses(StatisticsRequest $request): JsonResponse
    {
        $owner = $this->owner();
        $rows = $this->useCases->run(
            Actor::user($owner),
            new GetRevenueExpenses($owner, $request->filter()),
        );

        return $this->json($rows);
    }

    private function owner(): UserId
    {
        return UserId::fromInt((int) Auth::id());
    }

    /** @param list<array<string, mixed>> $rows */
    private function json(array $rows): JsonResponse
    {
        $responseFactory = response();

        return $responseFactory->json(['data' => $rows]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Payment\Commands\HandlePaymentCallback;
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    /**
     * Handle payment return from gateway
     */
    public function return(Request $request): RedirectResponse
    {
        $application = app();
        $gateway = $request->get('gateway', 'gopay');

        $paymentId = $request->query('id');

        Log::info('Payment return received', [
            'gateway' => $gateway,
            'paymentId' => $paymentId,
        ]);

        $result = $this->handleCallback($request);

        if ($result->success) {
            $status = $result->status ?? 'unknown';

            if ($status === 'completed') {
                $redirector = redirect();
                $redirect = $redirector->route('subscriptions.my-subscription', ['locale' => $application->getLocale()]);

                return $redirect->with(
                    'success',
                    __('payments.messages.successful'),
                );
            } elseif ($status === 'failed') {
                $redirector = redirect();
                $redirect = $redirector->route('subscriptions.index', ['locale' => $application->getLocale()]);

                return $redirect->with(
                    'error',
                    __('payments.messages.failed'),
                );
            }

            $redirector = redirect();
            $redirect = $redirector->route('subscriptions.index', ['locale' => $application->getLocale()]);

            return $redirect->with(
                'info',
                __('payments.messages.pending'),
            );
        }

        $redirector = redirect();
        $redirect = $redirector->route('subscriptions.index', ['locale' => $application->getLocale()]);

        return $redirect->with(
            'error',
            __('payments.messages.error'),
        );
    }

    /**
     * GoPay calls the notification URL with GET and the payment id in `id`, and repeats the call until it receives
     * HTTP 200, so a notification we could not process is answered with 503 to get it again.
     */
    public function notify(Request $request): JsonResponse
    {
        $gateway = $request->get('gateway', 'gopay');
        $paymentId = $request->query('id');

        Log::info('Payment notification received', [
            'gateway' => $gateway,
            'paymentId' => $paymentId,
        ]);

        $result = $this->handleCallback($request);

        if ($result->success) {
            $responseFactory = response();

            return $responseFactory->json(['status' => 'ok']);
        }

        $responseFactory = response();

        return $responseFactory->json(['status' => 'error'], 503);
    }

    private function handleCallback(Request $request): SubscriptionPaymentResultDTO
    {
        $paymentId = $request->query('id');
        if (!is_string($paymentId)) {
            $paymentId = null;
        }

        $gateway = $request->string('gateway', 'gopay');

        // Only the id: the gateway is asked for everything else
        return $this->useCases->run(
            Actor::anonymous(),
            new HandlePaymentCallback(
                $gateway->toString(),
                $paymentId,
            ),
        );
    }
}

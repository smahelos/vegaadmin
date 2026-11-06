<?php

namespace App\Http\Controllers\Frontend;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private PaymentApplicationServiceInterface $paymentApp;

    public function __construct(PaymentApplicationServiceInterface $paymentApp)
    {
        $this->paymentApp = $paymentApp;
    }

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

        // Only the id: the gateway is asked for everything else
        $result = $this->paymentApp->handleCallback(
            $gateway,
            ['id' => $paymentId],
        );

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
     * HTTP 200, so the answer is 200 whatever the processing did.
     */
    public function notify(Request $request): JsonResponse
    {
        $gateway = $request->get('gateway', 'gopay');
        $paymentId = $request->query('id');

        Log::info('Payment notification received', [
            'gateway' => $gateway,
            'paymentId' => $paymentId,
        ]);

        // Only the id: the gateway is asked for everything else
        $result = $this->paymentApp->handleCallback(
            $gateway,
            ['id' => $paymentId],
        );

        if ($result->success) {
            $responseFactory = response();

            return $responseFactory->json(['status' => 'ok']);
        }

        $responseFactory = response();

        return $responseFactory->json(['status' => 'error']);
    }
}

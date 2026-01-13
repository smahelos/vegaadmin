<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

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
        $gateway = $request->get('gateway', 'gopay');
        
        Log::info('Payment return received', [
            'gateway' => $gateway,
            'data' => $request->all(),
        ]);

        $result = $this->paymentApp->handleCallback($gateway, $request->all());

        if ($result->success) {
            $status = $result->status ?? 'unknown';

            if ($status === 'completed') {
                return redirect()->route('subscriptions.my-subscription', ['locale' => app()->getLocale()])
                    ->with('success', __('payment.payment_successful'));
            } elseif ($status === 'failed') {
                return redirect()->route('subscriptions.index', ['locale' => app()->getLocale()])
                    ->with('error', __('payment.payment_failed'));
            }
            
            return redirect()->route('subscriptions.index', ['locale' => app()->getLocale()])
                ->with('info', __('payment.payment_pending'));
        }

        return redirect()->route('subscriptions.index', ['locale' => app()->getLocale()])
            ->with('error', __('payment.payment_error'));
    }

    /**
     * Handle payment notification/webhook from gateway
     */
    public function notify(Request $request): JsonResponse
    {
        $gateway = $request->get('gateway', 'gopay');
        
        Log::info('Payment notification received', [
            'gateway' => $gateway,
            'data' => $request->all(),
        ]);

        $result = $this->paymentApp->handleCallback($gateway, $request->all());

        if ($result->success) {
                return response()->json(['status' => 'ok']);
            }

            return response()->json(['status' => 'error']);
        }
}

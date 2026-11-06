<?php

namespace App\Http\Controllers\Frontend;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    use AuthorizesRequests;

    private PaymentApplicationServiceInterface $paymentApp;

    public function __construct(PaymentApplicationServiceInterface $paymentApp)
    {
        $this->paymentApp = $paymentApp;
    }

    /**
     * Display subscription plans
     */
    public function index(): View
    {
        $planQuery = SubscriptionPlan::active();
        $plans = $planQuery->get();
        $userSubscription = null;

        if (Auth::check()) {
            $user = Auth::user();
            $subscriptions = $user->subscriptions();
            $subscriptions->where('status', 'active');
            $userSubscription = $subscriptions->first();
        }

        return view(
            'frontend.subscriptions.index',
            compact('plans', 'userSubscription'),
        );
    }

    /**
     * Show subscription plan details
     */
    public function show(string $locale, SubscriptionPlan $plan): View
    {
        // Gateways are still exposed by underlying subscription payment service; placeholder until unified list comes to application layer.
        $availableGateways = $this->paymentApp->getAvailableGateways();

        return view(
            'frontend.subscriptions.show',
            compact('plan', 'availableGateways'),
        );
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(
        Request $request,
        string $locale,
        SubscriptionPlan $plan,
    ): RedirectResponse {
        $request->validate([
            'gateway' => 'required|string|in:gopay',
        ]);

        $user = Auth::user();

        // Check if user already has active subscription
        $subscriptions = $user->subscriptions();
        $subscriptions->where('status', 'active');
        $existingSubscription = $subscriptions->first();

        if ($existingSubscription) {
            $redirector = redirect();
            $redirect = $redirector->route('subscriptions.index', ['locale' => $locale]);

            return $redirect->with(
                'error',
                __('subscriptions.messages.already_subscribed'),
            );
        }

        // Create subscription
        $subscription = Subscription::create(
            [
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'pending',
                'amount' => $plan->price,
                'currency' => $plan->currency,
            ],
        );

        // Outside production the URLs may point elsewhere (a tunnel to this machine); without an override they are ours
        $returnUrl = route('payment.return');
        $notifyUrl = route('payment.notify');
        if (!config('services.gopay.production')) {
            $returnUrl = config('services.gopay.test_return_url') ?: $returnUrl;
            $notifyUrl = config('services.gopay.test_notify_url') ?: $notifyUrl;
        }

        $application = app();
        $paymentData = [
            'return_url' => $returnUrl,
            'notify_url' => $notifyUrl,
            'lang' => $application->getLocale() === 'cs' ? 'CS' : 'EN',
        ];

        $result = $this->paymentApp->initiateSubscription(
            $subscription,
            $request->gateway,
            $paymentData,
        );

        if ($result->success && $result->redirectUrl) {
            return redirect($result->redirectUrl);
        }

        $subscription->delete();

        $redirector = redirect();
        $redirect = $redirector->route('subscriptions.show', ['locale' => $locale, 'plan' => $plan]);

        return $redirect->with(
            'error',
            __('subscriptions.messages.payment_failed'),
        );
    }

    /**
     * Cancel subscription
     */
    public function cancel(string $locale, Subscription $subscription): RedirectResponse
    {
        $this->authorize('update', $subscription);

        if ($subscription->cancel()) {
            // Delegation path – cancellation still uses domain service through application layer if exposed later.
            $this->paymentApp->cancelSubscription($subscription);

            $redirector = redirect();
            $redirect = $redirector->route('subscriptions.index', ['locale' => $locale]);

            return $redirect->with(
                'success',
                __('subscriptions.messages.cancelled'),
            );
        }

        $redirector = redirect();
        $redirect = $redirector->route('subscriptions.index', ['locale' => $locale]);

        return $redirect->with(
            'error',
            __('subscriptions.messages.cancellation_failed'),
        );
    }

    /**
     * View subscription details
     */
    public function mySubscription(): View|RedirectResponse
    {
        $user = Auth::user();
        $subscriptions = $user->subscriptions();
        $subscriptions->with(['subscriptionPlan', 'payments']);
        $subscriptions->where('status', 'active');
        $subscription = $subscriptions->first();

        if (!$subscription) {
            $redirector = redirect();
            $application = app();
            $redirect = $redirector->route('subscriptions.index', ['locale' => $application->getLocale()]);

            return $redirect->with(
                'info',
                __('subscriptions.messages.none_active'),
            );
        }

        return view(
            'frontend.subscriptions.my-subscription',
            compact('subscription'),
        );
    }
}

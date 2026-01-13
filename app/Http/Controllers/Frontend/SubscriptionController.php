<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
        $plans = SubscriptionPlan::active()->get();
        $userSubscription = null;
        
        if (Auth::check()) {
            $userSubscription = Auth::user()->subscriptions()
                ->where('status', 'active')
                ->first();
        }

        return view('frontend.subscriptions.index', compact('plans', 'userSubscription'));
    }

    /**
     * Show subscription plan details
     */
    public function show(string $locale, SubscriptionPlan $plan): View
    {
        // Gateways are still exposed by underlying subscription payment service; placeholder until unified list comes to application layer.
        $availableGateways = $this->paymentApp->getAvailableGateways();

        return view('frontend.subscriptions.show', compact('plan', 'availableGateways'));
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request, string $locale, SubscriptionPlan $plan): RedirectResponse
    {
        $request->validate([
            'gateway' => 'required|string|in:gopay',
        ]);

        $user = Auth::user();

        // Check if user already has active subscription
        $existingSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            return redirect()->route('subscriptions.index', ['locale' => $locale])
                ->with('error', __('subscription.already_subscribed'));
        }

        // Create subscription
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'amount' => $plan->price,
            'currency' => $plan->currency,
        ]);

        // Process payment
        $returnUrl = config('services.gopay.production') 
            ? route('payment.return') 
            : config('services.gopay.test_return_url', 'https://httpbin.org/get');
            
        $notifyUrl = config('services.gopay.production') 
            ? route('payment.notify') 
            : config('services.gopay.test_notify_url', 'https://httpbin.org/post');
            
        $paymentData = [
            'return_url' => $returnUrl,
            'notify_url' => $notifyUrl,
            'lang' => app()->getLocale() === 'cs' ? 'CS' : 'EN',
        ];

        $result = $this->paymentApp->initiateSubscription($subscription, $request->gateway, $paymentData);

        if ($result->success && $result->redirectUrl) {
            return redirect($result->redirectUrl);
        }

        $subscription->delete();

        return redirect()->route('subscriptions.show', ['locale' => $locale, 'plan' => $plan])
            ->with('error', $result->error ?? __('subscription.payment_failed'));
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

            return redirect()->route('subscriptions.index', ['locale' => $locale])
                ->with('success', __('subscription.cancelled_successfully'));
        }

        return redirect()->route('subscriptions.index', ['locale' => $locale])
            ->with('error', __('subscription.cancellation_failed'));
    }

    /**
     * View subscription details
     */
    public function mySubscription(): View|RedirectResponse
    {
        $subscription = Auth::user()->subscriptions()
            ->with(['subscriptionPlan', 'payments'])
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return redirect()->route('subscriptions.index', ['locale' => app()->getLocale()])
                ->with('info', __('subscription.no_active_subscription'));
        }

        return view('frontend.subscriptions.my-subscription', compact('subscription'));
    }
}

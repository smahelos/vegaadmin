<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Payment\Commands\CancelSubscription;
use App\Application\Payment\Commands\Subscribe;
use App\Application\Payment\Queries\FindActiveSubscription;
use App\Application\Payment\Services\PaymentApplicationService;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Payment\Exceptions\ActiveSubscriptionExistsException;
use App\Domain\Payment\ValueObjects\SubscriptionId;
use App\Domain\Payment\ValueObjects\SubscriptionPlanId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\SubscribeRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
        private readonly PaymentApplicationService $gateways,
    ) {}

    public function index(): View
    {
        $planQuery = SubscriptionPlan::active();
        $plans = $planQuery->get();
        $owner = $this->owner();
        $actor = Actor::user($owner);
        $userSubscription = null;
        // The plans are for anyone; only the current subscription needs subscription.view
        if ($this->useCases->allowed($actor, FindActiveSubscription::class)) {
            $userSubscription = $this->useCases->run(
                $actor,
                new FindActiveSubscription($owner),
            );
        }

        return view(
            'frontend.subscriptions.index',
            compact('plans', 'userSubscription'),
        );
    }

    public function show(string $locale, SubscriptionPlan $plan): View
    {
        $availableGateways = $this->gateways->getAvailableGateways();

        return view(
            'frontend.subscriptions.show',
            compact('plan', 'availableGateways'),
        );
    }

    public function subscribe(
        SubscribeRequest $request,
        string $locale,
        int $plan,
    ): RedirectResponse {
        // Outside production the URLs may point elsewhere (a tunnel to this machine); without an override they are ours
        $returnUrl = route('payment.return');
        $notifyUrl = route('payment.notify');
        if (!config('services.gopay.production')) {
            $returnUrl = config('services.gopay.test_return_url') ?: $returnUrl;
            $notifyUrl = config('services.gopay.test_notify_url') ?: $notifyUrl;
        }
        $language = 'EN';
        if ($locale === 'cs') {
            $language = 'CS';
        }

        $owner = $this->owner();
        try {
            // The route names the same use case in its `allowed` middleware: change them together
            $result = $this->useCases->run(
                Actor::user($owner),
                new Subscribe(
                    $owner,
                    SubscriptionPlanId::fromInt($plan),
                    $request->gateway(),
                    $returnUrl,
                    $notifyUrl,
                    $language,
                ),
            );
        } catch (ActiveSubscriptionExistsException) {
            $redirector = redirect();
            $redirect = $redirector->route('subscriptions.index', ['locale' => $locale]);

            return $redirect->with(
                'error',
                __('subscriptions.messages.already_subscribed'),
            );
        }

        if ($result->success && $result->redirectUrl) {
            return redirect($result->redirectUrl);
        }

        $redirector = redirect();
        $redirect = $redirector->route('subscriptions.show', ['locale' => $locale, 'plan' => $plan]);

        return $redirect->with(
            'error',
            __('subscriptions.messages.payment_failed'),
        );
    }

    public function cancel(string $locale, int $subscription): RedirectResponse
    {
        $owner = $this->owner();
        // The route names the same use case in its `allowed` middleware: change them together
        $this->useCases->run(
            Actor::user($owner),
            new CancelSubscription(
                $owner,
                SubscriptionId::fromInt($subscription),
            ),
        );

        $redirector = redirect();
        $redirect = $redirector->route('subscriptions.index', ['locale' => $locale]);

        return $redirect->with(
            'success',
            __('subscriptions.messages.cancelled'),
        );
    }

    public function mySubscription(string $locale): View|RedirectResponse
    {
        $owner = $this->owner();
        $active = $this->useCases->run(
            Actor::user($owner),
            new FindActiveSubscription($owner),
        );
        if ($active === null) {
            $redirector = redirect();
            $redirect = $redirector->route('subscriptions.index', ['locale' => $locale]);

            return $redirect->with(
                'info',
                __('subscriptions.messages.none_active'),
            );
        }

        // shortcut: the page reads the model with its plan and payments, TECH_DEBT step 19 "Subscription screens with placeholder content"
        $subscriptionQuery = Subscription::with(['subscriptionPlan', 'payments']);
        $subscription = $subscriptionQuery->findOrFail($active->id);

        return view(
            'frontend.subscriptions.my-subscription',
            compact('subscription'),
        );
    }

    private function owner(): UserId
    {
        return UserId::fromInt((int) Auth::id());
    }
}

<?php

namespace App\Exceptions;

use App\Domain\Expense\Exceptions\ExpenseNotFoundException;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Party\Exceptions\ArchivedReferenceSelectedException;
use App\Domain\Party\Exceptions\BankAccountArchivedException;
use App\Domain\Party\Exceptions\BankAccountNotFoundException;
use App\Domain\Party\Exceptions\BusinessArchivedException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\ContactIdentityChangedException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\Exceptions\DefaultBankAccountRequiredException;
use App\Domain\Party\Exceptions\DefaultBusinessRequiredException;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\Exceptions\IncompleteBusinessAddressException;
use App\Domain\Party\Exceptions\IncompleteDocumentPartyException;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Product\Exceptions\ProductInUseException;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\Exceptions\PermissionDeniedException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Prologue\Alerts\Facades\Alert;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ExceptionHandling
{
    /**
     * Register central exception render callbacks.
     */
    public static function registerCallbacks(Exceptions $exceptions): void
    {
        $exceptions->map(
            PermissionDeniedException::class,
            fn (PermissionDeniedException $e): AccessDeniedHttpException => new AccessDeniedHttpException('', $e),
        );

        $exceptions->render(
            fn (IncompleteDocumentPartyException $e, Request $request): JsonResponse|RedirectResponse => self::invalid(
                $request,
                'contact_id',
                __('parties.messages.incomplete_document_address'),
            ),
        );

        // A plan limit is reached: the message names the limit and the frontend links it to the plans
        $exceptions->render(
            function (EntityLimitExceededException $e, Request $request): JsonResponse|RedirectResponse {
                $message = self::limitMessage($e);

                if ($request->expectsJson()) {
                    $application = app();
                    $responseFactory = response();

                    return $responseFactory->json(
                        [
                            'message' => $message,
                            'plans_url' => route('subscriptions.index', ['locale' => $application->getLocale()]),
                        ],
                        403,
                    );
                }

                $session = session();
                $session->flash('limit_exceeded', true);

                return self::back($request, $message);
            },
        );

        // Another user's invoice, business, contact, account or product is answered like a missing one
        foreach (
            [
                InvoiceNotFoundException::class,
                ExpenseNotFoundException::class,
                BusinessNotFoundException::class,
                ContactNotFoundException::class,
                BankAccountNotFoundException::class,
                ProductNotFoundException::class,
            ] as $notFound
        ) {
            $exceptions->map(
                $notFound,
                fn (Throwable $e): NotFoundHttpException => new NotFoundHttpException('', $e),
            );
        }

        // A Czech company number another record of the user has already; on an invoice the customer saved to
        // the contacts has it
        $exceptions->render(
            fn (DuplicateCompanyNumberException $e, Request $request): JsonResponse|RedirectResponse => self::invalid(
                $request,
                self::isInvoiceRoute($request) ? 'customer_company_number' : 'company_number',
                __('parties.messages.duplicate_blocking'),
            ),
        );

        // The parties of a document: a new archived reference, a business without an address to print, a customer
        // that is no longer the selected contact
        $exceptions->render(
            fn (ArchivedReferenceSelectedException $e, Request $request): JsonResponse|RedirectResponse => self::invalid(
                $request,
                match (true) {
                    $e->reference instanceof BusinessId => 'business_id',
                    $e->reference instanceof BankAccountId => 'bank_account_id',
                    default => 'contact_id',
                },
                __('parties.validation.reference_archived'),
            ),
        );
        $exceptions->render(
            fn (IncompleteBusinessAddressException $e, Request $request): JsonResponse|RedirectResponse => self::invalid(
                $request,
                'business_id',
                __('parties.validation.business_address_incomplete'),
            ),
        );
        $exceptions->render(
            fn (ContactIdentityChangedException $e, Request $request): JsonResponse|RedirectResponse => self::invalid(
                $request,
                $request->routeIs('expense.*') ? 'supplier_company_number' : 'customer_company_number',
                __('parties.validation.contact_identity_changed'),
            ),
        );

        // Archiving a default needs another default first; an archived record cannot become one
        $exceptions->render(
            fn (DefaultBusinessRequiredException $e, Request $request): JsonResponse|RedirectResponse => self::back(
                $request,
                __('businesses.messages.default_required'),
            ),
        );
        $exceptions->render(
            fn (BusinessArchivedException $e, Request $request): JsonResponse|RedirectResponse => self::back(
                $request,
                __('businesses.messages.default_archived'),
            ),
        );
        $exceptions->render(
            fn (DefaultBankAccountRequiredException $e, Request $request): JsonResponse|RedirectResponse => self::back(
                $request,
                __('parties.bank_accounts.messages.default_required'),
            ),
        );
        $exceptions->render(
            fn (BankAccountArchivedException $e, Request $request): JsonResponse|RedirectResponse => self::back(
                $request,
                __('parties.bank_accounts.messages.default_archived'),
            ),
        );

        // A product on invoice lines stays; the user is told why instead of seeing a database error
        $exceptions->render(
            function (ProductInUseException $e, $request) {
                $message = __('products.messages.error_delete_invoices');
                if ($request->expectsJson()) {
                    $responseFactory = response();

                    return $responseFactory->json(['message' => $message], 409);
                }

                $redirect = back();

                return $redirect->with('error', $message);
            },
        );

        // Authentication failure on invoice routes
        // Web requests should redirect to localized frontend login, JSON should get 401
        $exceptions->render(
            function (AuthenticationException $e, $request) {
                if (self::isInvoiceRoute($request)) {
                    if ($request->expectsJson()) {
                        $responseFactory = response();

                        return $responseFactory->json(
                            [
                                'error' => __('users.auth.unauthenticated'),
                                'code' => 401,
                            ],
                            401,
                        );
                    }

                    // Let guests be redirected to the correct localized login page
                    $redirector = redirect();
                    $application = app();

                    return $redirector->guest(
                        route('frontend.login', ['locale' => $application->getLocale()]),
                    );
                }

                return null;
            },
        );

        // Generic invoice fallback (non JSON guest store)
        $exceptions->render(
            function (Throwable $e, $request) {
                if (self::isInvoiceRoute($request)) {
                    if ($e instanceof EntityLimitExceededException) {
                        return null; // handled above
                    }
                    // A denied permission and another user's invoice keep their 403 and 404
                    if ($e instanceof HttpExceptionInterface) {
                        return null;
                    }
                    // Let Laravel handle validation exceptions to preserve session error bag
                    if ($e instanceof ValidationException) {
                        return null;
                    }
                    // Skip handling for guest JSON store route (correct route name with dots)
                    if (
                        $request->routeIs('frontend.invoice.storeGuest')
                        || $request->routeIs('frontend.invoice.store.guest')
                    ) {
                        return null; // controller JSON handling
                    }

                    $redirector = redirect();
                    $redirect = $redirector->back();

                    return $redirect->with(
                        'error',
                        __('invoices.messages.update_error'),
                    );
                }

                return null;
            },
        );
    }

    private static function isInvoiceRoute(Request $request): bool
    {
        $route = $request->route();
        $routeName = $route?->getName();
        if (!$routeName) {
            return false;
        }

        return str_starts_with($routeName, 'frontend.invoice') || $routeName === 'frontend.invoices';
    }

    /**
     * Back to the form with a validation error on one field, or Laravel's 422 for JSON.
     */
    private static function invalid(
        Request $request,
        string $field,
        string $message,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            $responseFactory = response();

            return $responseFactory->json(
                [
                    'message' => $message,
                    'errors' => [$field => [$message]],
                ],
                422,
            );
        }

        $redirect = back();
        $redirect->withInput();

        return $redirect->withErrors([$field => $message]);
    }

    private static function limitMessage(EntityLimitExceededException $e): string
    {
        $replace = ['limit' => $e->limit];

        return match ($e->entity) {
            LimitedEntity::INVOICE => __('invoices.messages.limit_exceeded', $replace),
            LimitedEntity::EXPENSE => __('expenses.messages.limit_exceeded', $replace),
            LimitedEntity::BUSINESS => __('businesses.messages.limit_exceeded', $replace),
            LimitedEntity::CONTACT => __('contacts.messages.limit_exceeded', $replace),
            LimitedEntity::PRODUCT => __('products.messages.limit_exceeded', $replace),
        };
    }

    /**
     * Back to the form with a translated message: an alert in the admin, a flash message on the frontend.
     */
    private static function back(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            $responseFactory = response();

            return $responseFactory->json(['message' => $message], 409);
        }

        if (
            $request->is(
                config('backpack.base.route_prefix', 'admin') . '/*',
            )
        ) {
            $alert = Alert::error($message);
            $alert->flash();
            $redirect = back();

            return $redirect->withInput();
        }

        $redirect = back();
        $redirect->withInput();

        return $redirect->with('error', $message);
    }
}

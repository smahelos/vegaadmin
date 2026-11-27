# Payment context

Status: maintained map of the code on `main`, last checked 2026-11-23. Start with [the system overview](OVERVIEW.md). How gateways, callbacks, subscriptions and QR codes must work is in `rules/payments.md`; the code does not meet it everywhere yet, the gaps are steps 18 and 19 of [TECH_DEBT](../TECH_DEBT.md#step-18-payments). The user's bank accounts belong to Party ([PARTY.md](PARTY.md#entities)); the plan roles and their limits to User ([USER.md](USER.md#entity-limits)).

Subscriptions to a plan paid by card through GoPay, the QR payment code on invoices, and the reference data of banks and payment methods. Composition root: `PaymentServiceProvider`.

## Entities

| Entity | Table | Code |
|---|---|---|
| Subscription plan | `subscription_plans` (price, currency, `billing_period`, `billing_interval`, `is_active`) | `App\Models\SubscriptionPlan`, `SubscriptionPlanDTO`, `SubscriptionPlanId`, `BillingPeriod`; port `SubscriptionPlanReadRepositoryInterface` (`EloquentSubscriptionPlanReadRepository`) |
| Plan feature | `subscription_plan_features`, joined by `subscription_plan_subscription_plan_feature` | `App\Models\SubscriptionPlanFeature`: display lines of the plan pages, no limits |
| Subscription | `subscriptions`, owned by a user | `App\Models\Subscription`, `SubscriptionDTO`, `SubscriptionId`, `SubscriptionWriteData`, `SubscriptionStatus`; ports `SubscriptionDtoReadRepositoryInterface` and `SubscriptionDtoWriteRepositoryInterface` (`EloquentSubscriptionDtoReadRepository`, `EloquentSubscriptionDtoWriteRepository`, mapper `EloquentSubscriptionMapper`) |
| Payment | `payments`, one per gateway attempt of a subscription; status `pending`, `processing`, `completed`, `failed`, `cancelled`, `refunded`, `partially_refunded` (strings) | `App\Models\Payment`, `PaymentDTO`, `PaymentWriteData`; ports `PaymentDtoReadRepositoryInterface` and `PaymentDtoWriteRepositoryInterface` |
| Payment method | `payment_methods` (reference data) | `App\Models\PaymentMethod`, `PaymentMethodDTO`, `PaymentMethodCode` (the `slug`); port `PaymentMethodDtoReadRepositoryInterface` (`EloquentPaymentMethodDtoRepository`) |
| Bank | `banks` (reference data: bank codes per country) | `App\Models\Bank`, `BankDTO`; port `BankDtoRepository` (`EloquentBankDtoRepository`) |

Values: `BankAccount` (a Czech or Slovak account number with bank code, or an IBAN; `BankAccountProblem` says why one is refused), `QrPaymentDTO`, `PaymentMethodId`.

The `payments` table belongs to subscriptions: invoices have no payment records ("Invoices have no payment records" in [TECH_DEBT](../TECH_DEBT.md)). The Application ports `PaymentReadRepositoryInterface`, `PaymentWriteRepositoryInterface`, `SubscriptionReadRepositoryInterface` and `SubscriptionWriteRepositoryInterface` are bound in the provider but used by no code ("Two sets of repositories" in [TECH_DEBT](../TECH_DEBT.md)).

## Use cases

Commands in `app/Application/Payment/Commands/`, queries in `app/Application/Payment/Queries/`. The frontend uses `Frontend\SubscriptionController` (`subscriptions.*` routes) and `Frontend\PaymentController` (`payment.return`, `payment.notify`); the admin uses `Admin\SubscriptionCrudController`.

| Use case | Frontend | Admin | Access |
|---|---|---|---|
| `Subscribe` | `subscribe` (`allowed`) | — | `subscription.create` with owner |
| `CancelSubscription` | `cancel` (`allowed`) | — | `subscription.cancel` |
| `FindActiveSubscription` | `index`, `mySubscription` | — | `subscription.view` |
| `HandlePaymentCallback` | `return`, `notify` | — | public |
| `CreateSubscription`, `UpdateSubscription`, `DeleteSubscription` | — | `store`, `update`, `destroy` | `subscription.create` with owner, `subscription.update`, `subscription.delete` |

No use case is limited by an entity limit or a rate limiter. `Subscribe`, `CancelSubscription` and `HandlePaymentCallback` call the gateway, so they implement `CallsOutsideDatabaseInterface` and open their own transactions. The plan list and the plan detail (`index`, `show`) read `App\Models\SubscriptionPlan` directly and the gateway list through `PaymentApplicationService`, outside the gate.

Reference data without use cases, written by Backpack through the model: plans (`SubscriptionPlanCrudController`, `subscription_plan.*`; `store` and `update` sync the features), plan features (`SubscriptionPlanFeatureCrudController`, `subscription_plan_feature.*`), payment methods (`PaymentMethodCrudController`, `payment_method.*`) and banks (`BankCrudController`, `bank.*`).

## Subscribing and the gateway callback

| Step | Code |
|---|---|
| Plan pages | `resources/views/frontend/subscriptions/{index,show,my-subscription}.blade.php` |
| Validation | `SubscribeRequest`: the gateway, only `gopay` |
| Return and notify URLs | `SubscriptionController::subscribe()`; outside production `GOPAY_TEST_RETURN_URL` and `GOPAY_TEST_NOTIFY_URL` replace them (`config/services.php`, `gopay`) |
| Pending subscription | `SubscribeHandler`: refuses a second active subscription (`ActiveSubscriptionExistsException`), stores a `PENDING` subscription at the plan's price |
| Gateway payment | `SubscriptionPaymentService::processSubscriptionPayment()` stores a `pending` payment and calls `PaymentGatewayInterface::createPayment()` from `GatewayRegistryInterface`; the customer is redirected to the gateway's URL. A refused payment deletes the subscription again |
| Callback | GoPay calls `GET /payment/return` (the customer) and `GET /payment/notify` (server to server) with the payment id; both run `HandlePaymentCallback` with `Actor::anonymous()` |
| State | `SubscriptionPaymentService::handlePaymentCallback()`: `GoPayGateway::processPaymentReturn()` asks GoPay for the state (`verifyPayment()`); under a lock of the payment (`findByGatewayPaymentIdForUpdate()`) the status is stored, and the first `completed` activates the subscription |
| Activation | `activateById()` sets `ACTIVE` with the start now and the end from `BillingPeriod::end()` and the plan's interval |
| Answer | `notify` answers 200 after processing and 503 on any failure, so GoPay repeats it; `return` redirects with a flash message |

`GoPayGateway` is the only gateway (`app/Infrastructure/Providers/Payment/GoPayGateway.php`, credentials in `config/services.php`); `SubscriptionPaymentService` registers it in the registry under `gopay`. Tests replace it with the fakes in `tests/Support/Fakes/Payment/` and `tests/Support/Fakes/GoPay/`.

What is not built: activation does not assign the plan's role, so a subscription changes no permissions or limits ("Plan roles are not built", [TECH_DEBT](../TECH_DEBT.md#step-19-subscription-plans)); nothing renews or expires a subscription and `findActive()` ignores `ends_at` ("Subscriptions never renew or expire"); a failed payment is only logged; there are no refunds ("Build refunds"). The known defects of this flow (GoPay called inside the transaction, several pending subscriptions, a cancelled subscription activated by its payment, an inactive plan, the admin setting any status) are the items of step 18.

`CancelSubscriptionHandler` sets `CANCELLED` and then cancels the pending payments at the gateway (`cancelSubscriptionPayment()`).

## QR payments

`QrPaymentServiceInterface` (`QrPaymentService`) turns a `QrPaymentDTO` into a PNG data URL: `QrPaymentProviderFactory::autoDetectProvider()` picks the provider by the country of the account (`CzechQrPaymentProvider`, `SlovakQrPaymentProvider`, `EuropeanQrPaymentProvider` for the other SEPA countries) and, without one, by the currency (EUR European, otherwise Czech); the provider validates the data and builds the string, and `SimpleQrCodeRenderer` (`QrCodeRendererInterface`) renders it. Data the provider refuses gives no QR code. The variable symbol is the invoice number, the account the one stored on the invoice; `QrPaymentMapper` builds the DTO from Invoice's `PrintableInvoiceDTO` (`fromPrintableInvoice()`) or from the guest invoice's data (`fromSnapshot()`).

Callers: Invoice's `InvoicePdfRenderer` (only when the invoice is paid by bank transfer, `PaymentMethodCode::BANK_ACCOUNT`, and has an account) and `CreateGuestInvoiceHandler`. The variable symbol is a `VariableSymbol` (one to ten digits); the constant and specific symbols that the invoice prints do not go into the QR code. The invoice notes go in as the message, in ASCII on one line and cut to the format's length (`QrPaymentDTO::asciiMessage()`), with the QR Platba field separator `*` escaped as `%2A`: QR Platba reads ISO-8859-1, which lacks most Czech letters. A new country format is a new provider in `app/Domain/Payment/Providers/QrPayment/` and an entry of the factory.

## Payment methods and banks

- An invoice and an expense name their payment method (`payment_method_id`). `PaymentMethodCode::BANK_ACCOUNT` decides whether the invoice needs a bank account (`InvoiceRequest`) and a QR code; Invoice reads the methods through `PaymentMethodDtoReadRepositoryInterface`. Labels are `payment_methods.methods.*` in `lang/*/payment_methods.php`.
- The bank account forms of Party offer the banks of the owner's country through `BankApplicationService` (`ProvidesPartyOptions`); `BankAccountRule` validates the number through `BankAccount`.
- Analytics counts invoices by payment method (`GetPaymentMethods`).

## Events

No Payment use case publishes a domain event, and no observer watches the Payment models. A subscription changes nothing in other contexts yet (see "What is not built" above).

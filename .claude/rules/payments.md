---
paths:
  - "app/Domain/Payment/**"
  - "app/Application/Payment/**"
  - "app/Infrastructure/Gateways/**"
  - "app/Infrastructure/Providers/Payment/**"
  - "app/Http/Controllers/Frontend/PaymentController.php"
  - "app/Http/Controllers/Frontend/SubscriptionController.php"
  - "config/services.php"
---

# Payments

Card payments of subscriptions go through GoPay; invoices carry QR payment codes. Mistakes here charge people twice or activate plans nobody paid for.

## Gateways

- A gateway implements the Domain port `PaymentGatewayInterface` in Infrastructure (`app/Infrastructure/Gateways/Payment/`) and is registered in `GatewayRegistryInterface`. Domain and Application know only the port — never `GoPayGateway` or the GoPay SDK.
- Requests to the gateway are typed: amount as `Money` (checked by `PaymentAmount` against gateway limits), the subscriber's real name and email, return and notify URLs. A placeholder customer is a defect.
- Credentials come from `config/services.php` / `.env`; tests use the fakes in `tests/Support/Fakes/`.

## Callbacks and state changes

- A gateway notification or return request is only a hint that something changed. The handler reads the payment id from it, asks the gateway for the authoritative state (`getPaymentStatus()` / `verifyPayment()`) and acts on that answer — never on amounts or statuses from the request.
- Processing is idempotent: the same notification arriving twice changes nothing the second time (state transitions check the current state first, in one transaction).
- Callback routes are excluded from CSRF and answer the gateway with the status code it expects, independently of the outcome of our processing (`http.md`).
- Raw request payloads are logged without personal or card data.

## Subscriptions

- Activating, renewing and expiring a subscription is one domain operation that also sets the user's plan role and therefore their entity limits (`permissions.md`); nothing else changes a subscription's status.
- Plan length, price and limits come from the plan record, never from constants in code.

## QR payments

- The variable symbol is the invoice number (max 10 digits, `domain.md`); the account comes from the supplier's bank account through `BankAccount`.
- Country-specific QR formats are implementations of `QrPaymentProviderInterface`; adding a country means adding a provider, not a branch.

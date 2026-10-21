---
paths:
  - "app/Domain/**"
---

# Domain

Business rules in plain PHP. Allowed dependencies: `architecture.md`.

## Value objects

- A value object validates in its constructor or a named constructor (`fromString()`, `fromInt()`, `fromYearAndSequence()`) and throws a domain exception on invalid input; an instance is always valid.
- Immutable: operations return a new instance (`$a->add($b)`), equality through `equals()`.
- Identifiers are value objects (`InvoiceId`, `UserId`); a raw `int` id crosses into the Domain only at a named constructor.
- A value object that exists is used: wherever its concept is computed or validated, the code goes through it (due date → `InvoiceDueDate`, invoice number → `InvoiceNumber`, IBAN → `BankAccount`). A value object with no caller is deleted.

## Money and VAT

- Every amount is `Domain\Shared\Money\ValueObjects\Money`: decimal string amount plus currency, arithmetic through its methods with an explicit rounding strategy. Never `float` arithmetic on amounts; `Money::fromFloat()` only at a boundary that receives a float.
- Currency codes come from the `Currency` enum.
- VAT and totals are computed in the Domain (`Domain/Shared/Tax`, invoice services) from the line items, with one default tax rule for the whole app. Delivery and views only format `Money` through `MoneyFormatterInterface`.

## Time

- The current time comes from `ClockInterface::now()`; dates are `DateTimeImmutable`. No `new DateTime()`, `time()` or `date()` in the Domain.
- Periods and due dates are value objects (`InvoiceDueDate`), not ad-hoc `+ N days` arithmetic.

## Invoices

- Invoice number: four-digit year followed by the yearly sequence padded to at least four digits, sequence capped at 999999 so the number fits the 10-digit variable symbol of QR payments (`InvoiceNumber`). Unique per user and year; the next number is derived from the highest existing one through `InvoiceNumber`, never by string ordering.
- Line items are the source of truth for totals; an invoice's subtotal, VAT and total are derived from them.

## Services and errors

- Domain services are stateless and receive their collaborators as ports. They trust that Application has already checked ownership and permissions (`application.md`).
- Each context throws its own exceptions from `Exceptions/` (`InvoiceNotFoundException`); a domain method never signals failure by returning `false`.

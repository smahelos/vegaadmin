# D-046: Amounts Are Decimal Strings in `Money`, Without a Float Entry

Status: Accepted 2026-11-05
Recorded retrospectively: the rule in `.claude/rules/domain.md` and the invoice total changes existed before this ADR, without a recorded decision. The rationale and alternatives below are reconstructed from the code, its docblocks and commit messages; the original deliberation was not recorded. Removing the float entry is new in this decision.

## Context

Billex issues legal documents, and an amount feeds VAT, totals, PDFs and QR payments. A binary float cannot hold most decimal amounts exactly.

`Money` was introduced in 2025 as a non-negative decimal string plus a currency, with arithmetic over that string. It offered two entries: `fromString()`, and `fromFloat()`, described in its docblock as a "temporary helper – lossy, will be removed later". `fromFloat()` passed the amount through `number_format()` with 2 decimals, so it rounded silently, while `fromString()` keeps up to 8 decimals.

The rule "never `float` arithmetic on amounts" entered `domain.md` on 2026-10-21 and still allowed `fromFloat()` at a boundary that receives a float. On 2026-10-23 invoice totals moved to `Money` (commit `3a0d5500`): they had been computed in floats in four places, for example `Money::fromFloat($price->toFloat() * $q * $taxRate / 100, ...)`. The remaining production callers were removed step by step, until only an unreachable branch of `ProductDTO::fromArray` was left.

Amounts do not need a float to reach `Money`: a `decimal` column with Eloquent's `decimal:2` cast and form input both arrive as strings.

## Decision

1. **An amount is `Money`:** a non-negative decimal string plus a currency, in every layer above the mappers and form requests.
2. **`Money::fromString()` is the only entry.** It rejects negative amounts and a decimal comma, accepts up to 8 decimals and does not round. `Money::fromFloat()` and its only helper `assertAmount()` are removed.
3. **Storage is `decimal`.** The mapper builds `Money` from the column's string (`persistence.md`).
4. **Calculation uses the methods of `Money`,** with an explicit rounding strategy where the result is rounded. Amounts are not converted to `float` to calculate.
5. **A boundary that really receives a float converts it explicitly:** it rounds to the decimals that boundary defines, formats a decimal string and calls `fromString()`. `Money` offers no helper for this, so the rounding stays visible at the place responsible for it.
6. **Display goes through `MoneyFormatterInterface`.**

## Alternatives

- **Keep `fromFloat()` for boundaries.** It would be convenient for the rare float input, but its 2-decimal rounding is implicit and it makes `(float) $amount` look acceptable, which is how the earlier total calculations were written.
- **Store integer minor units.** This also avoids float errors, but it would rewrite the existing `Money` arithmetic, the `decimal` columns and the snapshots, and currencies and calculations with more than 2 decimals would need an extra scale.

## Consequences

- A rounding decision is always visible in the code that makes it. `fromString()` accepts `12.349` as it is, so the code that needs 2 decimals must limit or round them.
- Tests and fakes build amounts from strings.
- Known gaps remain, each in `docs/TECH_DEBT.md`: "Product prices are not `Money` on the write path" and "`MoneyFormatter` formats through a float". The non-negative invariant also awaits a decision for corrective documents ("Issued invoices can be changed and deleted").

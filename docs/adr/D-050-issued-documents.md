# D-050: Issued Documents Are Frozen at Issue and Corrected Only by Corrective Documents

Status: Accepted 2026-11-25
Product specification: [PRD: Issued documents](../prd/PRD_ISSUED_DOCUMENTS.md).
Related: [D-044](D-044-document-tables.md) (document tables, accepted with this decision), [D-046](D-046-money-amounts.md) (point 1 and 2 amended by this decision), [D-047](D-047-entity-limits.md) (the invoice quota counts issued invoices), [D-045](D-045-businesses-and-contacts.md) (parties stored on documents), step 12 of [TECH_DEBT](../TECH_DEBT.md#step-12-issued-invoices).

The decisions were agreed with the project owner on 2026-11-25 in a review of the whole step 12 plan. They replace the parts of the decisions of 2026-10-23 and 2026-11-24 recorded in TECH_DEBT that differ: separate number series with type prefixes, separate credit and debit notes, the cancelled state, existing invoices issued at their `created_at`, and the 36-month archive of invoices.

## Context

An issued invoice is a tax document. The law requires the authenticity of its origin, the integrity of its content and its legibility from issue to the end of its retention (§ 34 odst. 1 ZDPH), keeps it for 10 years from the end of the tax period of the supply (§ 35 odst. 2), and corrects its tax base or VAT only through a corrective tax document (§ 42, § 45). A correction of an accounting record shows who made it, when, and the content before and after (§ 35 ZÚ). The evidence number has no prescribed format and need not form a gapless series (§ 29 odst. 1 písm. e). Whoever states VAT on a document owes it (§ 108 odst. 4 písm. g), so a non-payer's document states no VAT. Every entrepreneur states its register entry on business documents (§ 435 odst. 1 OZ). These paragraphs were checked against the consolidated ZDPH for 2026, not with an accountant.

Today an invoice has no draft or issued state, stays editable in the frontend and the admin, is deleted for good, gets its PDF rendered again on every download in the language of whoever downloads it, prints one VAT total instead of a summary by rate, and its number is neither unique nor assigned at issue. Step 7 already stores the parties, the bank account and a copy of the supplier logo on the invoice, and the lines store copies of name, prices, unit and VAT rate.

Billex is not in production; no stored invoice has to be converted.

## Decision

### Life cycle

1. **A document is a draft or issued.** The state is derived from `issued_at` (draft: `NULL`) and recorded with `issued_by`; there is no state column. There is no cancelled state: a corrective document supplements the invoice, it does not cancel it.
2. **A draft is fully editable and deleted for good.** It has no number, no variable symbol and no QR code. Its PDF is a preview marked as not being a tax document, in any template.
3. **An issued document is never changed and never deleted.** The Domain refuses every change except the payment status of an invoice; no frontend, admin or console path writes anything else. The template, the document language, the parties and the contact link are fixed at issue. The lock is enforced in code, not by database triggers.
4. **Errors after issue are corrected by documents.** A formal error (a company number, a name, an address) is corrected by a corrective document without lines that carries the corrected parties and names the correction in its reason; an error in amounts, or a customer who wants one clean invoice, by a corrective document cancelling the whole amount and a new invoice copied from the old one. Editing an issued document with an audit log was rejected (see Alternatives).

### What issuing freezes

5. **Two layers, no JSON copy.** The locked rows of the document (parties, account, lines, VAT lines, totals, dates, currency, payment method, template, language, VAT payer flag, register entry) are the data that lists, statistics, the VAT return and corrective documents read. The PDF rendered at issue, stored with its SHA-256 hash, is the document as delivered: it alone fixes the language, the labels, the layout and the rounding of the rendering. The rows and the PDF agree because the rows never change after issue.
6. **Issuing copies what lives elsewhere.** The supplier business's VAT payer flag and register entry are copied to the document; the document language is a field of the draft, defaulting to the business's default language. A draft always reads the current values of the business; a later change of the business affects only documents issued later.
7. **VAT lines by rate are stored at issue** in the VAT line table shared by all document types ([D-044](D-044-document-tables.md), point 3): base, rate and VAT as `Money`. The PDF prints the summary by rate from them; statistics and the VAT return read them and never recompute. A non-payer's document has no VAT lines.
8. **The PDF is stored on a private disk** under `documents/{user_id}/{document_id}.pdf`, with `pdf_path` and `pdf_hash` on the document. Issuing is one transaction opened by the handler of a Command that calls outside the database (`CallsOutsideDatabaseInterface`): assign the number, write the rows, render the PDF (the number is in it), write the file, store the hash, commit. On any failure the transaction rolls back, the file is deleted and the number stays free. A download returns the stored file after checking its hash; a mismatch is an error logged as critical, never a silent re-render.

### Numbering

9. **One yearly series per owner for all issued document types,** in the `InvoiceNumber` format (4-digit year and a sequence up to 999999), without a type prefix. The number is digits only and is the variable symbol; the PDF title names the type. A unique index on the owner and the number refuses a used number; the domain exception is shown as a validation error. There is no lock and no counter table: two simultaneous issues that pick the same number end with one validation error.
10. **Issuing suggests the next number** (the highest number of the series of the issue date's year plus one); the user may enter another unused one. The issue date is prefilled with today from `ClockInterface` and may be changed; the year of the issue date selects the series. An empty tax point date is prefilled with the issue date.
11. **The guest invoice stays outside the life cycle:** it is never stored, its number is entered freely and prefilled from the year of `ClockInterface`, and nothing checks its uniqueness.

### Corrective documents

12. **One corrective document type.** The direction is the sign of its lines; a payer's document is a "corrective tax document" with VAT at the rates of the corrected invoice, a non-payer's a "corrective document" without VAT that states the supplier is not a VAT payer. The VAT payer flag comes from the corrected invoice, not from the business at the time of the correction.
13. **A corrective document always refers to the original invoice,** never to another corrective document; an invoice may have any number of them, and only an issued invoice can be corrected. It has its own draft, issue, number from the same series, PDF and hash.
14. **It stores only differences:** lines with a signed quantity and a non-negative unit price, each referring to the line it changes (`corrected_line_id`, empty for an added line), and the VAT lines of the differences. The current state of an invoice is its lines plus the differences of all its issued corrective documents; no quantity of the current state may fall below zero.
15. **Type-specific table `corrective_documents`** (primary key = foreign key to the document): the corrected invoice and the reason, both required and owned only by this type ([D-044](D-044-document-tables.md), criteria). The day the correction took place is stored in the common `tax_point_date`. The move from `invoices` to the common document table with a `type` column happens with the corrective documents, not before: numbering needs no type.
16. **A corrective document takes over** the currency, the document language, the template, the VAT payer flag and the bank account of the invoice; its parties are prefilled from the invoice and may be changed on its draft.

### Money and payment

17. **`Money` may be negative** ([D-046](D-046-money-amounts.md) amended). Line totals, VAT lines, document totals, statistics and the amount owed can be negative; non-negativity is checked where it is a rule of the value: unit prices, a QR payment amount, subscription amounts.
18. **The invoice carries the payment of the whole business case.** `payment_status` (`pending`, `paid`) exists only on invoices; `CANCELLED` leaves `InvoiceStatus`. The amount owed is the invoice total plus the totals of all its corrective documents (minus recorded payments from step 18). A positive corrective document prints a due date and a QR payment of its difference with the invoice's variable symbol; a negative one prints no QR payment. In step 18 the payment status stays a stored column, recomputed in the transaction that records a payment.

### Retention

19. **Issued documents and their PDFs stay in the main tables for 10 years while the account exists.** `db:archive`, `archive_policies` and `invoices_archive` are deleted. Exporting the documents before an account is erased belongs to step 24.

## Alternatives

| Option | Reason rejected |
|---|---|
| A separate JSON snapshot of the whole document at issue | A second copy of the same data with no constraints (rejected by D-044); it protects only against direct database edits, which the hash cannot detect either, because whoever edits the rows can edit the hash |
| Database triggers refusing updates of issued rows | Logic outside the code, migrations of triggers and an exception for the payment status, for a guarantee the Domain, the removed edit paths and tests already give |
| A number series per document type with prefixes (II, CN, DN) | Equal digits on two documents would share a variable symbol; prefixes need translations and a type in the unique index. The law requires neither separate series nor gapless numbering |
| Separate credit and debit note types | The law knows one corrective document; the direction is the sign of the difference, and a correction can raise one rate and lower another |
| A cancelled state set by a full corrective document | A corrective document supplements the invoice; both stay in the records and their sum is the final state, so a fully corrected invoice is a derived label, not a state |
| Editing an issued document with an audit log and a new PDF version | Contradicts the integrity of the content from issue (§ 34 ZDPH) and leaves two PDFs with the same number and different content |
| Unlocking an issued document until it is first sent or downloaded | Does not help once the customer has the document; a download is no reliable proof of delivery, and the draft PDF preview catches errors before issue |
| Keeping `Money` non-negative and deriving the sign from the document type | Every aggregate would need the type, and a correction that raises one VAT rate and lowers another cannot be expressed |
| Converting existing invoices to issued ones in a migration | Billex is not in production; the development database is rebuilt and the seeder issues its invoices through the use case, so they get a number, a PDF and a hash like any other |
| Keeping `db:archive` and extending the archive table | Archived documents would need the life cycle, the PDF path and the hash, and reading a document would look in two tables, for data that must stay readable for 10 years anyway |

## Consequences

- Step 12 delivers in branches: `ls-invoice-status-column` (the payment status column, `PARTIALLY_PAID` removed, the archive deleted), `ls-invoice-page-dtos`, `ls-business-invoice-settings` (VAT payer flag, register entry and default document language of the business), `ls-vat-by-rate` (VAT computed and printed by rate), `ls-invoice-issue` (life cycle, numbering, lock, VAT lines, copying the invoicing settings at issue, admin read-only, quota at issue), `ls-invoice-stored-pdf`, `ls-corrective-documents` (common document table, corrective documents, negative `Money`), and at any point `ls-business-invoice-defaults` (default currency, QR payment switch) and `ls-product-archive`.
- The admin shows documents read-only, with the stored PDF; its create, update and delete operations of invoices are removed. Only the owner issues a document.
- The monthly invoice quota ([D-047](D-047-entity-limits.md)) is checked at issue and counts issued invoices; drafts and corrective documents do not count.
- Statistics count the tax base of issued documents, corrective documents included, in the period of their tax point date or correction day; drafts do not count. Reminders go to issued unpaid invoices past their due date with the amount owed.
- Exchange rates (step 15) add their columns to the document later; no issued document predates them.
- The decisions that depend on the law are to be confirmed by an accountant before production (step 24): one series without prefixes, a corrective document without lines as the correction of a formal error, the non-payer's corrective document, the register entry text, the VAT summary by rate (§ 29 odst. 2 ZDPH) and the start of the 10-year period. Points 369 and later of amendment 461/2024 Sb. and the guidance of the Finanční správa were not checked.
- Revisit when an accountant's review contradicts one of these points, or when a document type needs to be issued without a number of the shared series.

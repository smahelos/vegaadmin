# PRD: Issued Documents

Date: 2026-11-25. Version: 1.0. Status: approved product specification (project owner, 2026-11-25).
Decision: [D-050](../adr/D-050-issued-documents.md). Related: [D-044](../adr/D-044-document-tables.md) (document tables), [D-046](../adr/D-046-money-amounts.md) (money), [PRD: Entity limits](PRD_ENTITY_LIMITS.md) (invoice quota), [PRD: Businesses and contacts](PRD_BUSINESSES_AND_CONTACTS.md) (parties on documents), step 12 of [TECH_DEBT](../TECH_DEBT.md#step-12-issued-invoices).

The product decisions were agreed with the project owner on 2026-11-25. The legal basis was checked against the consolidated ZDPH for 2026, not with an accountant; section 9 lists what an accountant confirms before production.

## 1. Problem and goal

An invoice a user issues is a tax document kept unchanged for 10 years. Today an invoice can be edited and deleted at any time, its PDF is rendered again on every download (in the language of whoever downloads it), it prints no VAT summary by rate, and its number is neither unique nor assigned at issue. Nothing corrects an invoice except overwriting it.

The goal is that:

- a user prepares an invoice as a draft, checks its PDF and issues it; from then on the document and its PDF never change;
- every correction after issue is a corrective document that refers to the invoice and states only the differences, as the law requires;
- numbers, amounts owed, reminders and statistics follow from the issued documents and their corrections.

## 2. Terms

- **Document**: an invoice or a corrective document the user issues.
- **Draft**: a document not yet issued. It has no number.
- **Issued document**: a document with a number, an issue moment (`issued_at`, `issued_by`) and a stored PDF with its hash.
- **Corrective document**: a document that corrects one issued invoice by stating differences. A VAT payer's is a "corrective tax document" (opravný daňový doklad), a non-payer's a "corrective document" (opravný doklad).
- **Business case**: an invoice together with all its issued corrective documents.
- **Current state of an invoice**: its lines plus the difference lines of all its issued corrective documents.
- **Amount owed**: the invoice total plus the totals of all its corrective documents, minus payments recorded in step 18.
- **Fully corrected invoice**: an invoice whose business case totals zero.

## 3. Life cycle

### 3.1 Draft

- A new invoice is a draft. The user edits all its fields and lines and may delete it; a deleted draft is gone for good.
- A draft has no number, no variable symbol and no QR payment.
- A draft reads the current VAT payer flag and register entry of the selected supplier business; the form and the preview follow them.
- The draft has a document language (cs, de, en, sk), prefilled with the default language of the supplier business.
- The user downloads a preview PDF of the draft in any of the templates. The preview is visibly marked "Draft – not a tax document" and has no number and no QR payment.
- The template is chosen on the draft.

### 3.2 Issuing

- Issuing opens a dialog with the number, the issue date and the tax point date.
- The number is prefilled with the next number of the series (3.4); the user may enter any other unused number in the `InvoiceNumber` format.
- The issue date is prefilled with today and may be changed; an empty tax point date is prefilled with the issue date. The due date is the issue date plus the due days.
- Issuing copies the supplier business's VAT payer flag and register entry to the document, stores the VAT lines by rate, renders the PDF in the document's language and template, and stores it with its hash.
- Issuing succeeds completely or not at all: if the number is used, the PDF cannot be rendered or the file cannot be written, nothing is stored and the number stays free. A used number is reported as a validation error of the number field with a new suggestion.
- Only the owner issues a document.

### 3.3 Issued document

- An issued document is never changed and never deleted, by the user, the admin or a console command.
- The user can open the edit form of an issued invoice: every field is shown read-only with the note "Issued document – changes only by a corrective document" and a link to create one. Only the payment status can be changed.
- "Save to contacts" and "Link to an existing contact" are offered only on drafts.
- A download returns the stored PDF. If its hash does not match, the download fails with an error and the mismatch is logged as critical.
- Every issued document offers "Copy to a new draft", which creates a draft with the same parties, lines and settings and no number.
- A fully corrected invoice shows the label "Fully corrected" in the list and the detail.

### 3.4 Numbering

- Each owner has one yearly series for all issued documents, invoices and corrective documents alike. The year of the issue date selects the series.
- The number is digits only (`InvoiceNumber`: 4-digit year and a sequence up to 999999) and is the variable symbol. It carries no type prefix; the PDF title names the type.
- The suggested number is the highest number of the series plus one. The series may have gaps (a user-entered number, a deleted draft never had one).
- A number is unique per owner; two documents of the same owner never share a number, whatever their types.
- The guest invoice is not stored and has no life cycle: its number is entered freely, prefilled with the first number of the current year, and nothing checks its uniqueness.

## 4. Correcting an issued invoice

### 4.1 When to use what

| Situation | What the user does |
|---|---|
| A formal error without a change of amount (company number, name, address, text) | Issues a corrective document without lines, with the corrected parties and the reason naming the correction |
| Returned goods, an additional discount, a wrong price, a wrong VAT rate, an additional charge | Issues a corrective document with the differences |
| The whole invoice is wrong, or the customer wants one clean invoice | Issues a corrective document cancelling every line, then "Copy to a new draft", fixes the draft and issues it as a new invoice with a new number |

### 4.2 Corrective document

- A corrective document is created from an issued invoice and always refers to that original invoice, never to another corrective document. An invoice may have any number of corrective documents; a draft invoice has none.
- It is a draft first, like an invoice, and is issued the same way: its number comes from the same series, its PDF and hash are stored, and afterwards it never changes.
- It takes over from the invoice the currency, the document language, the template, the bank account and the VAT payer flag. A VAT payer's corrective document is a "corrective tax document" with VAT at the invoice's rates; a non-payer's is a "corrective document" without VAT that states "Not a VAT payer", even when the business has registered for VAT since.
- Its parties are prefilled from the invoice and can be changed on its draft (a customer who has moved, a corrected company number).
- It has a reason (required) and the day the correction took place, prefilled with the issue date.
- A VAT payer's form shows the deadline for delivering it: the day of the correction plus 15 days (§ 42 odst. 5 ZDPH).
- A positive corrective document has a due date, prefilled from the invoice's due days, and a QR payment of its difference with the invoice's variable symbol. A negative one has no QR payment and states that the difference will be refunded or set off.

### 4.3 Entering the differences

- The form loads the current state of the invoice: every line with its current quantity.
- On each loaded line the user can:
  - **cancel** it: the line is marked as cancelled (highlighted in its own colour, not struck through) and its whole amount counts as a negative difference;
  - **change its quantity** up or down: the line is highlighted in another colour and the change counts as a difference; a quantity of 0 marks the line as cancelled.
- The user adds new lines as on an invoice (description, quantity, unit price, VAT rate); on a corrective document an added line may have a negative quantity (a discount).
- A wrong price or VAT rate is corrected by cancelling the line and adding a line with the right price or rate.
- The form shows three totals: the original total, the difference and the new state. The issue dialog shows the difference and says explicitly when the corrective document cancels the whole invoice.
- No quantity of the current state may fall below zero.
- A corrective document without any change has no lines and a difference of zero (formal correction).

### 4.4 What the corrective document stores and prints

- Only differences: one line per change with its signed quantity and non-negative unit price, referring to the invoice line or earlier corrective line it changes; added lines refer to none.
- The VAT lines of the differences by rate, stored at issue.
- The PDF prints the title by type, its number, the number and issue date of the corrected invoice, both parties (with VAT numbers for a payer), the register entry, the reason, the issue date and the day of the correction, the difference lines, the differences of the tax base and VAT by rate and the difference in total. It does not print a new final price.

## 5. Payment, reminders and statistics

- Only an invoice has a payment status, `pending` or `paid`; it stands for the whole business case. A corrective document has none.
- The payment status "cancelled" and "partially paid" no longer exist. Recording partial payments comes with step 18, which must be delivered before production.
- The amount owed is shown on the invoice detail. Reminders go to issued, unpaid invoices past their due date (and on the days before it that the reminder task sets) with an amount owed above zero; the reminder and its QR payment state the amount owed. A fully corrected invoice gets no reminder.
- The overdue count follows the same rule as the reminders.
- Revenue in the statistics is the tax base (a non-payer's total) of issued invoices and corrective documents, each in the period of its tax point date or day of correction. A corrective document lowering the price lowers the revenue of its own period; the corrected invoice stays in its own period. Drafts never count.
- Statistics read the stored VAT lines and totals; they never recompute documents.

## 6. Supplier business settings

The business form gets a section "Invoicing":

| Field | Delivered with | Used for |
|---|---|---|
| VAT payer (yes/no) | `ls-invoice-issue` | VAT on new drafts; copied to the document at issue |
| Register entry (free text, optional, e.g. "C 12345 vedená u Krajského soudu v Brně" or "zapsán v živnostenském rejstříku") | `ls-invoice-issue` | printed with the supplier; copied at issue |
| Default document language | `ls-invoice-issue` | prefills the draft's document language |
| Default currency | `ls-business-invoice-defaults` | prefills the draft's currency |
| QR payment on documents (yes/no) | `ls-business-invoice-defaults` | whether the PDF prints the QR payment |

A non-payer's document states no VAT and states "Not a VAT payer".

## 7. Admin, limits and retention

- The admin shows invoices and corrective documents read-only: the list, the detail and the stored PDF. It cannot create, edit, issue or delete them.
- The monthly invoice quota counts invoices issued in the quota window and is checked at issue ([PRD: Entity limits](PRD_ENTITY_LIMITS.md), section 3). Drafts and corrective documents do not count; creating drafts is limited only by the rate limit.
- Issued documents and their PDFs are kept in the main tables for 10 years while the account exists. There is no automatic archiving of documents: `db:archive`, its archive policies and the invoice archive table are removed.
- Exporting all documents and PDFs before an account is erased is step 24, not this document.

## 8. Exclusions

- Recording payments, partial payments and refunds, bank matching (step 18).
- Exchange rates stored with the document (step 15).
- Sending documents by e-mail (step 14).
- Advance invoices and other document types.
- Changing the template or the language of an issued document, and editing an issued document with an audit log.
- Corrective documents of corrective documents, and corrections of a draft.
- Editing the price of a loaded line directly on the corrective form (done as cancel and add).
- The export of documents before an account is erased (step 24).

## 9. Legal points to confirm with an accountant

To be confirmed before production (step 24), not before delivering this document:

- one series for invoices and corrective documents, without prefixes;
- a corrective document without lines as the correction of a formal error;
- the non-payer's corrective document, its title and content;
- the content of the register entry for each kind of entrepreneur;
- the VAT summary by rate (§ 29 odst. 2 ZDPH) and the content of the corrective tax document (§ 45 odst. 1);
- the start of the 10-year retention period;
- points 369 and later of amendment 461/2024 Sb. and the guidance of the Finanční správa.

## 10. Acceptance criteria

| ID | Criterion |
|---|---|
| A01 | A new invoice is a draft without a number; it can be edited and deleted, and a deleted draft cannot be restored. |
| A02 | The preview PDF of a draft is marked "Draft – not a tax document", has no number and no QR payment, and can be downloaded in each template. |
| A03 | Issuing suggests the highest number of the issue date's year plus one; a user-entered unused number is accepted; a used number is refused as a validation error of the number field and nothing is stored. |
| A04 | Two simultaneous issues with the same number result in one issued document and one validation error. |
| A05 | An invoice issued with an issue date in the previous year takes its number from that year's series. |
| A06 | An issued invoice and a corrective document of the same owner never share a number. |
| A07 | Issuing stores `issued_at`, `issued_by`, the VAT payer flag, the register entry, the VAT lines by rate and the PDF with its SHA-256 hash; if rendering or writing the PDF fails, nothing is stored and the number stays free. |
| A08 | Downloading an issued document returns the stored PDF byte for byte; after the business, the template, the translations or the user's language change, the download is unchanged. |
| A09 | A stored PDF whose hash does not match is not returned; the error is logged as critical. |
| A10 | Every attempt to change an issued document other than its payment status is refused, through the frontend form, the admin and the Domain; an issued document cannot be deleted. |
| A11 | The edit form of an issued invoice shows all fields read-only and allows only the payment status to change. |
| A12 | "Save to contacts" and "Link to an existing contact" are not offered on an issued document. |
| A13 | A payer's issued invoice prints the base and VAT for each rate; a non-payer's prints no VAT and states "Not a VAT payer". |
| A14 | A change of the business's VAT payer flag or register entry changes drafts and later issued documents, never an issued one. |
| A15 | The PDF of an issued document is in the document language chosen on the draft, prefilled with the business's default language. |
| A16 | "Copy to a new draft" creates a draft with the same parties, lines and settings and no number. |
| A17 | A corrective document can be created only from an issued invoice and refers to it; a further corrective document of the same invoice loads the current state including the earlier corrections. |
| A18 | On the corrective form, cancelling a line or changing its quantity highlights the line and updates the original total, the difference and the new state; a quantity of 0 marks the line as cancelled; no quantity of the current state can fall below zero. |
| A19 | The issued corrective document stores and prints only the difference lines, the differences by rate and the difference in total, with the corrected invoice's number and issue date, the reason and the day of the correction. |
| A20 | A corrective document of an invoice issued by a non-payer has no VAT and the title "corrective document", even after the business became a VAT payer; a payer's has the title "corrective tax document" and the invoice's rates. |
| A21 | A corrective document without lines can be issued with changed parties; its difference is zero. |
| A22 | A payer's corrective form shows the delivery deadline of 15 days from the day of the correction. |
| A23 | A positive corrective document prints a QR payment of its difference with the invoice's variable symbol; a negative one prints none. |
| A24 | The amount owed of an invoice is its total plus all its corrective documents; an invoice whose case totals zero is labelled "Fully corrected" and gets no reminder. |
| A25 | Reminders and the overdue count include only issued, unpaid invoices past their due date with an amount owed above zero, and state the amount owed. |
| A26 | Revenue counts the tax base of issued documents in the period of their tax point date or day of correction, corrective documents with their sign; drafts are not counted. |
| A27 | The admin shows documents and their stored PDFs read-only and offers no create, edit, issue or delete. |
| A28 | The monthly invoice quota is checked at issue and counts issued invoices; drafts and corrective documents do not count. |
| A29 | `db:archive` and the archive policies no longer exist; documents older than 36 months stay readable with their PDFs. |
| A30 | The guest invoice is not stored, its number is free and prefilled from the current year of the clock. |
| A31 | The business form has the VAT payer flag, the register entry and the default document language (`ls-invoice-issue`), the default currency and the QR payment switch (`ls-business-invoice-defaults`). |

## 11. Delivery

| Branch | Sections | Criteria |
|---|---|---|
| `ls-invoice-status-column` | 5 (payment status `pending`/`paid` stored as a string, `PARTIALLY_PAID` removed), 7 (archive removed) | A29 |
| `ls-invoice-page-dtos` | typed page DTOs of the invoice pages; no product behaviour | — |
| `ls-invoice-issue` | 3, 5 (without corrective documents), 6 (first three fields), 7 (admin, quota) | A01–A07, A10–A16, A25 (without corrective documents), A26 (invoices), A27, A28, A30, A31 (first part) |
| `ls-invoice-stored-pdf` | 3.2 and 3.3 (stored PDF, hash, download) | A07 (PDF part), A08, A09 |
| `ls-corrective-documents` | 4, 5 (amount owed, statistics with corrective documents) | A17–A26 |
| `ls-business-invoice-defaults` | 6 (last two fields) | A31 (second part) |

`CANCELLED` leaves `InvoiceStatus` in `ls-invoice-issue`. The development database is rebuilt with `migrate:fresh --seed`, and `DevelopmentDataSeeder` issues its invoices through the issuing use case; no migration converts stored invoices.

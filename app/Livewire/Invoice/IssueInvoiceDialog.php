<?php

declare(strict_types=1);

namespace App\Livewire\Invoice;

use App\Application\Invoice\Commands\IssueInvoice;
use App\Application\Invoice\Queries\GetIssueInvoiceForm;
use App\Domain\Invoice\Exceptions\InvoiceIssuedException;
use App\Domain\Invoice\Exceptions\InvoiceNumberTakenException;
use App\Domain\Invoice\Exceptions\InvoiceNumberYearException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Http\Requests\Invoice\InvoiceWriteDataInput;
use App\Livewire\Shared\ActsForUser;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The issue dialog of a draft: number and dates are fixed only here (D-050, point 10). One instance per page,
 * opened by the browser event `open-issue-dialog` with the invoice id.
 */
final class IssueInvoiceDialog extends Component
{
    use ActsForUser;

    #[Locked]
    public ?int $invoiceId = null;

    public string $number = '';

    public string $issueDate = '';

    public string $taxPointDate = '';

    public ?string $limitMessage = null;

    /** "Save and issue" of the edit form returns to the detail with the dialog of the saved draft open. */
    public function mount(?int $openFor = null): void
    {
        if ($openFor !== null) {
            $this->open($openFor);
        }
    }

    #[On('open-issue-dialog')]
    public function open(int $id): void
    {
        $this->requireAllowed(IssueInvoice::class);
        $this->resetValidation();
        $this->limitMessage = null;
        try {
            $form = $this->runUseCase(
                new GetIssueInvoiceForm(
                    $this->userId(),
                    InvoiceId::fromInt($id),
                ),
            );
        } catch (InvoiceIssuedException) {
            $this->toDetail(
                $id,
                'error',
                __('invoices.messages.issued_locked'),
            );

            return;
        }

        $this->invoiceId = $id;
        $this->number = $form->number->value;
        $this->issueDate = $form->issueDate->format('Y-m-d');
        $this->taxPointDate = '';
    }

    public function close(): void
    {
        $this->invoiceId = null;
    }

    public function issue(): void
    {
        $this->requireAllowed(IssueInvoice::class);
        $id = $this->invoiceId;
        abort_if($id === null, 404);
        $this->limitMessage = null;
        $this->validate();

        try {
            $this->runUseCase(
                new IssueInvoice(
                    $this->userId(),
                    InvoiceId::fromInt($id),
                    new InvoiceNumber($this->number),
                    new DateTimeImmutable($this->issueDate),
                    $this->taxPointDate === '' ? null : new DateTimeImmutable($this->taxPointDate),
                ),
            );
        } catch (InvoiceNumberYearException $e) {
            $this->addError(
                'number',
                __('invoices.validation.number_year', ['suggestion' => $e->suggestion->value]),
            );

            return;
        } catch (InvoiceNumberTakenException $e) {
            $this->addError(
                'number',
                __('invoices.validation.number_taken', ['suggestion' => $e->suggestion?->value]),
            );

            return;
        } catch (EntityLimitExceededException $e) {
            $this->limitMessage = __('invoices.messages.limit_exceeded', ['limit' => $e->limit]);

            return;
        } catch (InvoiceIssuedException) {
            $this->toDetail(
                $id,
                'error',
                __('invoices.messages.issued_locked'),
            );

            return;
        }

        $this->toDetail(
            $id,
            'success',
            __('invoices.messages.issued'),
        );
    }

    public function render(): View
    {
        return view('livewire.invoice.issue-invoice-dialog');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'number' => InvoiceWriteDataInput::numberRules(),
            'issueDate' => 'required|date',
            'taxPointDate' => 'nullable|date',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'number' => __('invoices.fields.invoice_vs'),
            'issueDate' => __('invoices.fields.issue_date'),
            'taxPointDate' => __('invoices.fields.tax_point_date'),
        ];
    }

    private function toDetail(
        int $id,
        string $kind,
        string $message,
    ): void {
        $session = session();
        $session->flash($kind, $message);
        $this->redirectRoute(
            'frontend.invoice.show',
            ['locale' => App::getLocale(), 'id' => $id],
        );
    }
}

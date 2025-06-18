<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

class InvoiceOverdueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invoice;
    protected $recipientType;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invoice $invoice, string $recipientType = 'supplier')
    {
        $this->invoice = $invoice;
        $this->recipientType = $recipientType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Set locale based on the recipient's preferred language
        $locale = $notifiable->preferredLocale() ?? config('app.fallback_locale', 'en');
        App::setLocale($locale);

        $dueDate = $this->invoice->due_date->format('d.m.Y');
        $daysOverdue = $this->invoice->due_date->diffInDays(Carbon::now());
        
        $viewName = $this->recipientType === 'client' 
            ? 'emails.invoices.reminders.overdue_client' 
            : 'emails.invoices.reminders.overdue_supplier';
        
        return (new MailMessage)
            ->subject(__('invoices.reminders.overdue_subject', ['number' => $this->invoice->invoice_vs]))
            ->view($viewName, [
                'invoice' => $this->invoice,
                'dueDate' => $dueDate,
                'daysOverdue' => $daysOverdue,
                'greeting' => __('invoices.reminders.greeting', ['name' => $notifiable->name]),
                'locale' => $locale,
            ]);
    }
}

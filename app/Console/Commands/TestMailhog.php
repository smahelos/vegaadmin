<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Send a test email to verify mail configuration
 */
class TestMailhog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-mailhog {--to=test@example.com : Email address to send test message to} {--subject=Test Email from VegaAdmin : Subject of the test email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify mail configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('to');
        $subject = $this->option('subject');
        
        $this->info('Sending test email to: ' . $email);
        $this->info('Subject: ' . $subject);
        $this->info('Current configuration:');
        $this->info('MAIL_MAILER: ' . config('mail.default'));
        $this->info('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->info('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        
        try {
            Mail::raw('Test email from VegaAdmin application at: ' . now(), function ($message) use ($email, $subject) {
                $message->to($email)
                    ->subject($subject);
            });
            $this->info('Email sent successfully! Check Mailhog at: http://localhost:8025');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error sending email: ' . $e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

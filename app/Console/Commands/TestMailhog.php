<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailhog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email? : E-mail pro odeslání testovací zprávy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Odeslání testovacího e-mailu pro ověření konfigurace';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?: 'test@example.com';
        
        $this->info('Odesílám testovací e-mail na adresu: ' . $email);
        $this->info('Aktuální konfigurace:');
        $this->info('MAIL_MAILER: ' . config('mail.default'));
        $this->info('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->info('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        
        try {
            Mail::raw('Test e-mailu z aplikace VegaAdmin v čase: ' . now(), function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test e-mailu z VegaAdmin');
            });
            $this->info('E-mail byl odeslán! Zkontroluj Mailhog na adrese: http://localhost:8025');
        } catch (\Exception $e) {
            $this->error('Chyba při odesílání e-mailu: ' . $e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
        }
    }
}

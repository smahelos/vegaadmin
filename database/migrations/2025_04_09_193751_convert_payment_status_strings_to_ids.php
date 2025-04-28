<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ConvertPaymentStatusStringsToIds extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // 1. Zjistíme, zda tabulka invoices obsahuje sloupec payment_status_id
        if (!Schema::hasColumn('invoices', 'payment_status_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_status_id')->nullable()->after('payment_status');
            });
        }

        // 2. Získáme všechny stavy z tabulky statuses
        $statuses = DB::table('statuses')->get()->keyBy('name');
        
        // 3. Mapujeme běžné názvy stavů na záznamy z tabulky (pro případ, že chybí některé záznamy)
        $statusMap = [
            'Uhrazená' => 'paid',
            'Čeká na zaplacení' => 'pending',
            'Po splatnosti' => 'overdue',
            'Částečně uhrazená' => 'partially_paid',
            'Stornovaná' => 'cancelled',
            // Přidejte další mapování, pokud je potřeba
        ];

        // 4. Projdeme všechny faktury, které mají vyplněné řetězcové payment_status
        $invoices = DB::table('invoices')
            ->whereNotNull('payment_status')
            ->whereNull('payment_status_id')
            ->get();

        foreach ($invoices as $invoice) {
            $statusName = $invoice->payment_status;
            $statusId = null;
            
            // Hledání přímo podle názvu stavu
            if (isset($statuses[$statusName])) {
                $statusId = $statuses[$statusName]->id;
            } 
            // Hledání podle klíčů z mapování
            elseif (isset($statusMap[$statusName]) && isset($statuses[$statusMap[$statusName]])) {
                $statusId = $statuses[$statusMap[$statusName]]->id;
            }
            // Pokud stav neexistuje, vytvoříme ho
            else {
                $slug = \Illuminate\Support\Str::slug($statusName);
                $statusId = DB::table('statuses')->insertGetId([
                    'name' => $statusName,
                    'slug' => $slug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Přidáme do naší cache
                $newStatus = (object) [
                    'id' => $statusId,
                    'name' => $statusName,
                    'slug' => $slug
                ];
                $statuses[$statusName] = $newStatus;
            }
            
            // Aktualizace faktury - přidání ID stavu
            DB::table('invoices')
                ->where('id', $invoice->id)
                ->update(['payment_status_id' => $statusId]);
        }
        
        // Nyní máme vše převedeno, původní sloupec nechání pro zpětnou kompatibilitu
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // Při rollbacku pouze odstraníme nově přidaný sloupec
        if (Schema::hasColumn('invoices', 'payment_status_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('payment_status_id');
            });
        }
    }
}
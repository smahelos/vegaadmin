<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Procházíme všechny faktury s invoice_text
        $invoices = Invoice::whereNotNull('invoice_text')->get();
        
        foreach ($invoices as $invoice) {
            try {
                $invoiceData = json_decode($invoice->invoice_text, true);
                
                // Pokud máme platná JSON data a položky
                if (is_array($invoiceData) && isset($invoiceData['items']) && is_array($invoiceData['items'])) {
                    foreach ($invoiceData['items'] as $item) {
                        // Zkontrolujeme, jestli je položka custom nebo existující produkt
                        $isCustomProduct = !isset($item['product_id']) || empty($item['product_id']);
                        
                        // Vypočítáme hodnoty
                        $price = $item['price'] ?? 0;
                        $quantity = $item['quantity'] ?? 1;
                        $taxRate = $item['tax_rate'] ?? 21;
                        $taxAmount = ($price * $quantity * $taxRate) / 100;
                        $totalPrice = ($price * $quantity) + $taxAmount;
                        
                        // Vložíme záznam do pivotní tabulky
                        DB::table('invoice_product')->insert([
                            'invoice_id' => $invoice->id,
                            'product_id' => $isCustomProduct ? null : ($item['product_id'] ?? null),
                            'name' => $item['name'] ?? 'Unnamed Product',
                            'quantity' => $quantity,
                            'price' => $price,
                            'currency' => $item['currency'] ?? 'CZK',
                            'unit' => $item['unit'] ?? 'ks',
                            'category' => $item['category'] ?? null,
                            'description' => $item['description'] ?? null,
                            'is_custom_product' => $isCustomProduct,
                            'tax_rate' => $taxRate,
                            'tax_amount' => $taxAmount,
                            'total_price' => $totalPrice,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to migrate invoice #' . $invoice->id . ' data: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     * Nelze snadno vrátit, ale měli bychom zachovat zálohu dat
     */
    public function down(): void
    {
        // Není implementováno, vyžadovalo by zálohu dat
    }
};

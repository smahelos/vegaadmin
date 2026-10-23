<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Units were free text (Czech abbreviations, English keys or null). They become values of
     * App\Domain\Shared\Unit\Enums\Unit; a missing or unknown unit counts in pieces.
     */
    private const ABBREVIATIONS = [
        'ks' => 'pieces',
        'piece' => 'pieces',
        'pcs' => 'pieces',
        'hod' => 'hours',
        'kg' => 'kilograms',
        'g' => 'grams',
        'l' => 'liters',
        'ml' => 'milliliters',
        'm' => 'meters',
        'cm' => 'centimeters',
        'm³' => 'cubic_meters',
        'cm³' => 'cubic_centimeters',
    ];

    private const UNITS = [
        'pieces',
        'hours',
        'days',
        'kilograms',
        'grams',
        'liters',
        'milliliters',
        'meters',
        'centimeters',
        'cubic_meters',
        'cubic_centimeters',
    ];

    public function up(): void
    {
        foreach (['products', 'invoice_products'] as $table) {
            foreach (self::ABBREVIATIONS as $abbreviation => $unit) {
                DB::table($table)->where('unit', $abbreviation)->update(['unit' => $unit]);
            }
            DB::table($table)
                ->where(fn ($query) => $query->whereNull('unit')->orWhereNotIn('unit', self::UNITS))
                ->update(['unit' => 'pieces']);

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('unit')->default('pieces')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['products', 'invoice_products'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('unit')->nullable()->default(null)->change();
            });
        }
    }
};

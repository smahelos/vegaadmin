<?php

declare(strict_types=1);

// The rule checks classes under App\Http\Controllers, so the fixtures live there
namespace App\Http\Controllers\EloquentFixture;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class LoadsData
{
    public function index(?Tax $tax): Collection
    {
        $taxQuery = Tax::query();
        $taxQuery->where('rate', 21);
        $tax?->save();
        DB::table('taxes');
        $taxClass = Tax::class;
        $taxClass::all();

        $taxes = Tax::all();

        return $taxes->map(
            fn (Tax $listed): string => $listed->getKey() . Tax::count(),
        );
    }

    public function filter(): void
    {
        $clause = fn (Builder $query): Builder => $query->whereNull('archived_at');
        $clause(Tax::query());
    }
}

final class StillLoadsData
{
    public function index(): void
    {
        Tax::all();
    }
}

final class LoadsNoData
{
    public function index(): string
    {
        return 'no data';
    }
}

namespace App\Infrastructure\EloquentFixture;

use App\Models\Tax;

final class Repository
{
    public function all(): void
    {
        Tax::all();
    }
}

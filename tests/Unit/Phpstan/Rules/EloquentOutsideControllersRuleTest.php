<?php

declare(strict_types=1);

namespace Tests\Unit\Phpstan\Rules;

use Billex\PHPStan\Collectors\EloquentCallCollector;
use Billex\PHPStan\Rules\EloquentOutsideControllersRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @extends RuleTestCase<EloquentOutsideControllersRule>
 */
final class EloquentOutsideControllersRuleTest extends RuleTestCase
{
    private const LOADS_DATA = 'App\Http\Controllers\EloquentFixture\LoadsData';

    private const STILL_LOADS_DATA = 'App\Http\Controllers\EloquentFixture\StillLoadsData';

    private const LOADS_NO_DATA = 'App\Http\Controllers\EloquentFixture\LoadsNoData';

    private const ADVICE = ': a controller runs a use case, which loads and writes the data.';

    protected function getRule(): Rule
    {
        return new EloquentOutsideControllersRule(
            [
                self::STILL_LOADS_DATA,
                self::LOADS_NO_DATA,
            ],
        );
    }

    protected function getCollectors(): array
    {
        return [new EloquentCallCollector];
    }

    #[Test]
    public function controllers_calling_eloquent_are_reported_and_the_list_only_shrinks(): void
    {
        $this->analyse(
            [__DIR__ . '/data/eloquent-in-controllers.php'],
            [
                [self::LOADS_DATA . ' calls App\Models\Tax::query()' . self::ADVICE, 17],
                [self::LOADS_DATA . ' calls Illuminate\Database\Eloquent\Builder<App\Models\Tax>::where()' . self::ADVICE, 18],
                [self::LOADS_DATA . ' calls App\Models\Tax::save()' . self::ADVICE, 19],
                [self::LOADS_DATA . ' calls Illuminate\Support\Facades\DB::table()' . self::ADVICE, 20],
                [self::LOADS_DATA . ' calls App\Models\Tax::all()' . self::ADVICE, 22],
                [self::LOADS_DATA . ' calls App\Models\Tax::all()' . self::ADVICE, 24],
                [self::LOADS_DATA . ' calls App\Models\Tax::count()' . self::ADVICE, 27],
                [self::LOADS_DATA . ' calls App\Models\Tax::getKey()' . self::ADVICE, 27],
                [self::LOADS_DATA . ' calls Illuminate\Database\Eloquent\Builder<App\Models\Tax>::whereNull()' . self::ADVICE, 33],
                [self::LOADS_DATA . ' calls App\Models\Tax::query()' . self::ADVICE, 34],
                [self::LOADS_NO_DATA . ' calls no Eloquent: remove it from the controllers of this rule in phpstan.neon.', -1],
            ],
        );
    }
}

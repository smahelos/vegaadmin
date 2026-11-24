<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use App\Models\PageCategory;
use Database\Seeders\PageContentSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PageContentSeederTest extends TestCase
{
    #[Test]
    public function running_again_keeps_one_category_per_slug(): void
    {
        $this->seed(PageContentSeeder::class);
        $this->seed(PageContentSeeder::class);

        foreach (
            [
                'hlavni-stranky',
                'pravni',
                'podpora',
                'funkce',
            ] as $slug
        ) {
            $categoryQuery = PageCategory::where('slug->cs', $slug);
            $this->assertSame(
                1,
                $categoryQuery->count(),
                $slug,
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Forms\Shared;

use App\Infrastructure\Forms\Shared\StatusFormFields;
use App\Models\Status;
use App\Models\StatusCategory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class StatusFormFieldsTest extends TestCase
{
    #[Test]
    public function names_are_the_active_statuses_of_one_category(): void
    {
        $categoryFactory = StatusCategory::factory();
        $category = $categoryFactory->create();
        $statusFactory = Status::factory();
        $active = $statusFactory->create(['category_id' => $category->id]);
        $statusFactory->create(
            [
                'category_id' => $category->id,
                'is_active' => false,
            ],
        );
        $statusFactory->create();

        $names = StatusFormFields::names($category->slug);

        $this->assertSame([$active->id => $active->name], $names);
    }
}

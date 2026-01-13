<?php

namespace Tests\Feature\Domain\Shared\File;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;

class ProductImageValidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function rejects_disallowed_extension(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $file = UploadedFile::fake()->create('malicious.exe', 10, 'application/octet-stream');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $product->image = $file;
        $product->save();
    }

    #[Test]
    public function rejects_oversized_file(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        // 11 MB (KB calculation: 11 * 1024 = 11264)
        $file = UploadedFile::fake()->create('big.png', 11264, 'image/png');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $product->image = $file;
        $product->save();
    }

    #[Test]
    public function accepts_valid_image(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $file = UploadedFile::fake()->image('ok.png', 100, 100);
        $product->image = $file;
        $product->save();
        $this->assertNotEmpty($product->image);
    }
}

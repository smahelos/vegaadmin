<?php

namespace Tests\Feature\Domain\Shared\File;

use App\Models\FileHash;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductImageHashDedupIntegrationTest extends TestCase
{
    #[Test]
    public function product_image_dedup_reuses_file(): void
    {
        Storage::fake('public');
        $productFactory = Product::factory();
        $product = $productFactory->create();
        $fileFactory = UploadedFile::fake();
        $file1 = $fileFactory->image(
            'p1.png',
            220,
            220,
        );
        $tmpCopy = tempnam(sys_get_temp_dir(), 'dup');
        copy($file1->getRealPath(), $tmpCopy);
        $file2 = new UploadedFile(
            $tmpCopy,
            'p2.png',
            'image/png',
            null,
            true,
        );

        $product->image = $file1;
        $product->save();
        $path1 = $product->image;

        $product->image = $file2;
        $product->save();
        $path2 = $product->image;

        $this->assertSame($path1, $path2);
        $fileHashQuery = FileHash::where('path', $path1);
        $record = $fileHashQuery->first();
        $this->assertNotNull($record);
        $this->assertGreaterThanOrEqual(2, $record->ref_count);
    }
}

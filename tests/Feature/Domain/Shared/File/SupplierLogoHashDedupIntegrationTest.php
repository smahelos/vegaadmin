<?php

namespace Tests\Feature\Domain\Shared\File;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\FileHash;
use App\Models\Supplier;
use PHPUnit\Framework\Attributes\Test;

class SupplierLogoHashDedupIntegrationTest extends TestCase
{
    use RefreshDatabase; // Ensure migrations for users and file_hashes tables are present
    #[Test]
    public function supplier_logo_uses_deduplication(): void
    {
        Storage::fake('public');
        $file1 = UploadedFile::fake()->image('logo.png', 140, 140);
        $tmpCopy = tempnam(sys_get_temp_dir(), 'dup');
        copy($file1->getRealPath(), $tmpCopy);
        $file2 = new UploadedFile($tmpCopy, 'other.png', 'image/png', null, true);

        $supplier = Supplier::factory()->create();
        $supplier->supplier_logo = $file1;
        $supplier->save();
        $path1 = $supplier->supplier_logo;

        $supplier->supplier_logo = $file2;
        $supplier->save();
        $path2 = $supplier->supplier_logo;

        $this->assertSame($path1, $path2);
        $record = FileHash::where('path', $path1)->first();
        $this->assertNotNull($record);
        $this->assertGreaterThanOrEqual(2, $record->ref_count);
    }
}

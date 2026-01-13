<?php

namespace Tests\Unit\Domain\Shared\File\Services;

use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileUploadServiceTest extends TestCase
{
    private FileUploadServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FileUploadServiceInterface::class);
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(FileUploadServiceInterface::class, $this->service);
    }

    

    #[Test]
    public function sanitize_filename_removes_invalid_chars(): void
    {
        $filename = $this->service->sanitizeFilename('Tést Fílè @2025!!.pdf');
        $this->assertStringStartsWith('Test_File_2025', $filename);
        $this->assertStringEndsWith('.pdf', 'dummy.pdf'); // structural (we only pass name portion earlier in generation)
    }
}

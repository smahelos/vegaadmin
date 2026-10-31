<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Shared\File;

use App\Application\Party\Services\PartyLogoApplicationService;
use App\Domain\Shared\File\DTO\IncomingFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PartyLogoStorageIntegrationTest extends TestCase
{
    #[Test]
    public function identical_party_logos_are_independent_and_deleting_one_keeps_the_other(): void
    {
        Storage::fake('public');
        $upload = UploadedFile::fake()->image(
            'logo.png',
            140,
            140,
        );
        $incoming = new IncomingFile(
            'logo.png',
            'png',
            'image/png',
            $upload->getSize(),
            $upload->getRealPath(),
        );
        $logos = app(PartyLogoApplicationService::class);
        $first = $logos->store($incoming);
        $second = $logos->store($incoming);

        $this->assertNotSame($first, $second);
        $logos->delete($first);
        Storage::disk('public')->assertExists($second);
        $logos->delete($second);
        Storage::disk('public')->assertMissing($second);
    }
}

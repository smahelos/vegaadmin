<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Services\PartyLogoApplicationService;
use App\Domain\Shared\File\DTO\IncomingFile;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

trait EditsPartyLogo
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $logo = null;

    #[Locked]
    public ?string $currentLogo = null;

    public bool $removeLogo = false;

    private function logoPath(PartyLogoApplicationService $logos): ?string
    {
        if ($this->logo !== null) {
            return $logos->store(new IncomingFile(
                $this->logo->getClientOriginalName(),
                strtolower($this->logo->getClientOriginalExtension()),
                (string) $this->logo->getMimeType(),
                (int) $this->logo->getSize(),
                (string) $this->logo->getRealPath(),
            ));
        }

        return $this->removeLogo ? null : $this->currentLogo;
    }

    private function discardNewLogo(PartyLogoApplicationService $logos, ?string $logo): void
    {
        if ($logo !== null && $logo !== $this->currentLogo) {
            $logos->delete($logo);
        }
    }
}

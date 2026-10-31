<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Party\Services\PartyLogoApplicationService;
use App\Domain\Shared\File\DTO\IncomingFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

trait StoresPartyLogo
{
    /**
     * @template T
     *
     * @param  callable(?string): T  $save
     * @return T
     */
    private function withStoredLogo(
        FormRequest $request,
        PartyLogoApplicationService $logos,
        ?string $current,
        callable $save,
    ): mixed {
        $logo = $this->storeLogo(
            $request,
            $logos,
            $current,
        );
        $saved = false;
        try {
            $result = $save($logo);
            $saved = true;

            return $result;
        } finally {
            $discard = $saved ? $current : $logo;
            if ($logo !== $current && $discard !== null) {
                $logos->delete($discard);
            }
        }
    }

    private function storeLogo(
        FormRequest $request,
        PartyLogoApplicationService $logos,
        ?string $current,
    ): ?string {
        $file = $request->file('logo');
        if (! $file instanceof UploadedFile) {
            return $request->boolean('remove_logo') ? null : $current;
        }

        return $logos->store(new IncomingFile(
            $file->getClientOriginalName(),
            strtolower($file->getClientOriginalExtension()),
            (string) $file->getMimeType(),
            (int) $file->getSize(),
            (string) $file->getRealPath(),
        ));
    }
}

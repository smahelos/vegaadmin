<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Domain\Invoice\Contracts\DocumentLogoStorageInterface;
use App\Domain\Shared\File\DTO\IncomingFile;

final class DocumentLogoApplicationService
{
    public function __construct(private readonly DocumentLogoStorageInterface $storage) {}

    /**
     * The callback commits the document before the old file is removed. On failure only its new copy is removed.
     *
     * @template T
     *
     * @param  callable(?string): T  $save
     * @return T
     */
    public function saveWithLogo(
        ?string $stored,
        bool $keep,
        ?string $source,
        ?IncomingFile $upload,
        callable $save,
    ): mixed {
        $logo = $stored;
        $saved = false;
        try {
            if (! $keep) {
                $logo = null;
                if ($upload !== null) {
                    $logo = $this->storage->store($upload);
                } elseif ($source !== null) {
                    $logo = $this->storage->copy($source);
                }
            }
            $result = $save($logo);
            $saved = true;

            return $result;
        } finally {
            $discard = $saved ? $stored : $logo;
            if ($logo !== $stored && $discard !== null) {
                $this->storage->delete($discard);
            }
        }
    }
}

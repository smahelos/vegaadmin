<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Shared\File\DTO\IncomingFile;

interface DocumentLogoStorageInterface
{
    public function copy(string $source): string;

    public function store(IncomingFile $file): string;

    public function delete(string $path): void;
}

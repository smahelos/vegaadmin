<?php

declare(strict_types=1);

namespace App\Application\Expense\Support;

use App\Domain\Expense\DTO\ExpenseWriteData;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use RuntimeException;

final class ExpenseAttachments
{
    public function __construct(private readonly FileUploadServiceInterface $files) {}

    /**
     * The callback commits the expense before the removed files are deleted. On failure only the new uploads are removed.
     * It calls the given upload inside its transaction: the upload counts file hashes, which a rollback must revert first.
     *
     * @template T
     *
     * @param  list<string>  $stored
     * @param  callable(callable(): list<string>): T  $save
     * @return T
     */
    public function saveWithAttachments(
        array $stored,
        ExpenseWriteData $data,
        callable $save,
    ): mixed {
        $removed = array_values(
            array_intersect($stored, $data->removedAttachments),
        );
        $attachments = array_values(
            array_diff($stored, $removed),
        );
        $uploaded = [];
        $saved = false;
        try {
            $result = $save(
                function () use ($data, $attachments, &$uploaded): array {
                    foreach ($data->uploads as $upload) {
                        $path = $this->files->handleFileUpload(
                            $upload,
                            'attachments',
                            'expenses/attachments',
                            [
                                'disk' => 'public',
                                'sanitizeFilename' => true,
                                'randomizeFilename' => true,
                            ],
                            null,
                            'attachment',
                        ) ?? throw new RuntimeException('The attachment was not stored');
                        $uploaded[] = $path;
                    }

                    return [...$attachments, ...$uploaded];
                },
            );
            $saved = true;
        } finally {
            foreach ($saved ? $removed : $uploaded as $path) {
                if ($saved) {
                    $this->files->deleteFile($path, 'public');
                } else {
                    $this->files->discardRolledBackUpload($path, 'public');
                }
            }
        }

        return $result;
    }
}

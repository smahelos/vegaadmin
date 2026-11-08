<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Domain\Invoice\Contracts\ExpenseReadRepositoryInterface;
use App\Domain\Invoice\Contracts\ExpenseWriteRepositoryInterface;
use App\Domain\Invoice\DTO\ExpenseDTO;
use App\Domain\Invoice\DTO\ExpenseWriteData;
use App\Domain\Invoice\Exceptions\ExpenseAccessDeniedException;
use App\Domain\Invoice\Exceptions\ExpenseNotFoundException;
use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Domain\User\Contracts\UserPermissionServiceInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;
use App\Domain\User\ValueObjects\UserId;
use RuntimeException;

final class ExpenseApplicationService
{
    public function __construct(
        private readonly ExpenseReadRepositoryInterface $reader,
        private readonly ExpenseWriteRepositoryInterface $writer,
        private readonly ExpensePartiesAssembler $parties,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly EntityLimit $limits,
        private readonly UserPermissionServiceInterface $permissions,
        private readonly FileUploadServiceInterface $files,
        private readonly DocumentLogoApplicationService $logos,
        private readonly ContactReadRepositoryInterface $contacts,
    ) {}

    public function get(UserId $userId, ExpenseId $id): ExpenseDTO
    {
        return $this->reader->findByIdForUser($id, $userId) ?? throw new ExpenseNotFoundException('Expense not accessible for user');
    }

    public function create(
        UserId $userId,
        ExpenseWriteData $data,
        UserId $actorId,
    ): ExpenseDTO {
        $this->assertPermission($actorId, 'can_create_expense');

        return $this->save(
            $userId,
            $data,
            null,
        );
    }

    public function update(
        UserId $userId,
        ExpenseId $id,
        ExpenseWriteData $data,
        UserId $actorId,
    ): ExpenseDTO {
        $this->assertPermission($actorId, 'can_update_expense');

        return $this->save(
            $userId,
            $data,
            $id,
        );
    }

    private function assertPermission(UserId $actorId, string $permission): void
    {
        if (
            !in_array(
                $permission,
                $this->permissions->getUserPermissions($actorId->toInt()),
                true,
            )
        ) {
            throw new ExpenseAccessDeniedException('The administrator cannot change expenses');
        }
    }

    private function save(
        UserId $userId,
        ExpenseWriteData $data,
        ?ExpenseId $id,
    ): ExpenseDTO {
        $uploaded = [];
        $removed = [];
        $saved = false;
        try {
            $stored = $id === null ? null : $this->get($userId, $id);
            if ($data->contactId === null) {
                $contact = null;
            } else {
                $contact = $this->contacts->findByIdForUser($data->contactId, $userId)
                    ?? throw new ContactNotFoundException($data->contactId);
            }
            $sameSupplier = $stored !== null && $stored->parties->contactId?->value === $data->contactId?->value;
            $keepLogo = $sameSupplier && ($data->contactId !== null || ($data->supplierLogo === null && !$data->removeSupplierLogo));
            $expense = $this->logos->saveWithLogo(
                $stored?->parties->supplierLogo,
                $keepLogo,
                $contact?->logo,
                $data->contactId === null ? $data->supplierLogo : null,
                function (?string $logo) use ($userId, $data, $id, $stored, &$uploaded, &$removed): ExpenseDTO {
                    return $this->transactions->transaction(
                        function () use ($userId, $data, $id, $stored, $logo, &$uploaded, &$removed): ExpenseDTO {
                            if ($id === null) {
                                $this->limits->assertCanCreate($userId, LimitedEntity::EXPENSE);
                            }
                            $parties = $this->parties->assemble(
                                $userId,
                                $data,
                                $stored?->parties,
                            );
                            $parties = $parties->withSupplierLogo($logo);
                            $attachments = $stored?->attachments ?? [];
                            $removed = array_values(
                                array_intersect($attachments, $data->removedAttachments),
                            );
                            $attachments = array_values(
                                array_diff($attachments, $removed),
                            );
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
                                $attachments[] = $path;
                            }
                            if ($id === null) {
                                $expense = $this->writer->create(
                                    $userId,
                                    $data,
                                    $parties,
                                    $attachments,
                                );
                            } else {
                                $expense = $this->writer->update(
                                    $id,
                                    $userId,
                                    $data,
                                    $parties,
                                    $attachments,
                                );
                            }

                            return $expense;
                        },
                    );
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

        return $expense;
    }
}

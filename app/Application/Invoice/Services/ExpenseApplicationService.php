<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Domain\Invoice\Contracts\ExpenseReadRepositoryInterface;
use App\Domain\Invoice\Contracts\ExpenseWriteRepositoryInterface;
use App\Domain\Invoice\DTO\ExpenseDTO;
use App\Domain\Invoice\DTO\ExpenseWriteData;
use App\Domain\Invoice\Events\ExpenseCreated;
use App\Domain\Invoice\Exceptions\ExpenseAccessDeniedException;
use App\Domain\Invoice\Exceptions\ExpenseNotFoundException;
use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Domain\User\Contracts\UserPermissionServiceInterface;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\ValueObjects\UserId;
use RuntimeException;

final class ExpenseApplicationService
{
    public function __construct(
        private readonly ExpenseReadRepositoryInterface $reader,
        private readonly ExpenseWriteRepositoryInterface $writer,
        private readonly ExpensePartiesAssembler $parties,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly UniversalLimitServiceInterface $limits,
        private readonly UserPermissionServiceInterface $permissions,
        private readonly EventPublisherInterface $events,
        private readonly FileUploadServiceInterface $files,
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
        if (! in_array(
            $permission,
            $this->permissions->getUserPermissions($actorId->toInt(), 'backpack'),
            true,
        )) {
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
            $expense = $this->transactions->transaction(function () use ($userId, $data, $id, &$uploaded, &$removed): ExpenseDTO {
                $stored = $id === null ? null : $this->get($userId, $id);
                if ($id === null && ! in_array(
                    'can_create_expense_unlimited',
                    $this->permissions->getUserPermissions($userId->toInt(), 'backpack'),
                    true,
                )) {
                    $check = $this->limits->checkLimit(
                        $userId->toInt(),
                        'expense',
                        'count',
                        $this->limits->getBestPeriodType($userId->toInt(), 'expense'),
                    );
                    if (! $check['allowed']) {
                        throw new EntityLimitExceededException($check);
                    }
                }
                $parties = $this->parties->assemble(
                    $userId,
                    $data,
                    $stored?->parties,
                );
                $attachments = $stored?->attachments ?? [];
                $removed = array_values(array_intersect($attachments, $data->removedAttachments));
                $attachments = array_values(array_diff($attachments, $removed));
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
                $expense = $id === null
                    ? $this->writer->create(
                        $userId,
                        $data,
                        $parties,
                        $attachments,
                    )
                    : $this->writer->update(
                        $id,
                        $userId,
                        $data,
                        $parties,
                        $attachments,
                    );
                if ($id === null) {
                    $this->events->publish(new ExpenseCreated($expense->id, $userId));
                }

                return $expense;
            });
            $saved = true;
        } finally {
            foreach ($saved ? $removed : $uploaded as $path) {
                $this->files->deleteFile($path, 'public');
            }
        }

        return $expense;
    }
}

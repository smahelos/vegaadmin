<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Operations;

use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Illuminate\Database\QueryException;

/**
 * Backpack's delete operation for entries that documents refer to through restricting
 * foreign keys: a refused delete answers with a notification instead of a database error.
 */
trait RestrictedDeleteOperation
{
    use DeleteOperation {
        destroy as deleteEntry;
    }

    /** MySQL ER_ROW_IS_REFERENCED_2: a foreign key with ON DELETE RESTRICT refused the delete */
    private const ROW_IS_REFERENCED = 1451;

    /**
     * @return string|array{error: list<string>} "1" when deleted; Backpack's delete button shows the notification
     */
    public function destroy(int|string $id): string|array
    {
        try {
            return $this->deleteEntry($id);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) !== self::ROW_IS_REFERENCED) {
                throw $e;
            }

            return ['error' => [__('general.messages.delete_referenced')]];
        }
    }
}

<?php

namespace App\Application\User\Contracts;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;

/**
 * Service for configuring Backpack CRUD access based on user permissions.
 */
interface CrudAccessServiceInterface
{
    /**
     * Configure CRUD access for given user on a CrudPanel instance.
     *
     * @param CrudPanel $crud CrudPanel instance to configure
     * @param int $userId ID of the user whose permissions are evaluated
     * @return void
     */
    public function configureCrudAccess(CrudPanel $crud, int $userId): void;
}

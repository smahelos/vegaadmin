<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class ManageUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:permissions 
                            {action : Action to perform (list-roles, list-permissions, assign-role, remove-role, show-user)}
                            {--user= : User ID or email}
                            {--role= : Role name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage user roles and permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list-roles':
                return $this->listRoles();
            case 'list-permissions':
                return $this->listPermissions();
            case 'assign-role':
                return $this->assignRole();
            case 'remove-role':
                return $this->removeRole();
            case 'show-user':
                return $this->showUser();
            default:
                $this->error('Invalid action. Available actions: list-roles, list-permissions, assign-role, remove-role, show-user');
                return 1;
        }
    }

    /**
     * List all available roles and their permissions
     */
    private function listRoles(): int
    {
        $roles = Role::with('permissions')->get();
        
        $this->info('Available Roles and their Permissions:');
        $this->line('');
        
        foreach ($roles as $role) {
            $this->line("<comment>Role:</comment> {$role->name}");
            $this->line("<comment>Permissions:</comment>");
            
            if ($role->permissions->count() > 0) {
                foreach ($role->permissions as $permission) {
                    $this->line("  - {$permission->name}");
                }
            } else {
                $this->line("  No permissions assigned");
            }
            $this->line('');
        }
        
        return 0;
    }

    /**
     * List all available permissions
     */
    private function listPermissions(): int
    {
        $permissions = Permission::all();
        
        $this->info('Available Permissions:');
        $this->line('');
        
        foreach ($permissions as $permission) {
            $this->line("- {$permission->name}");
        }
        
        return 0;
    }

    /**
     * Assign role to user
     */
    private function assignRole(): int
    {
        $userInput = $this->option('user');
        $roleName = $this->option('role');

        if (!$userInput || !$roleName) {
            $this->error('Both --user and --role options are required for assign-role action');
            return 1;
        }

        $user = $this->findUser($userInput);
        if (!$user) {
            return 1;
        }

        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $this->error("Role '{$roleName}' not found");
            return 1;
        }

        if ($user->hasRole($roleName)) {
            $this->warn("User already has role '{$roleName}'");
            return 0; // This is not an error, just a warning
        }

        $user->assignRole($role);
        $this->info("Role '{$roleName}' assigned to user {$user->email}");
        return 0;
    }

    /**
     * Remove role from user
     */
    private function removeRole(): int
    {
        $userInput = $this->option('user');
        $roleName = $this->option('role');

        if (!$userInput || !$roleName) {
            $this->error('Both --user and --role options are required for remove-role action');
            return 1;
        }

        $user = $this->findUser($userInput);
        if (!$user) {
            return 1;
        }

        if (!$user->hasRole($roleName)) {
            $this->warn("User does not have role '{$roleName}'");
            return 0; // This is not an error, just a warning
        }

        $user->removeRole($roleName);
        $this->info("Role '{$roleName}' removed from user {$user->email}");
        return 0;
    }

    /**
     * Show user details with roles and permissions
     */
    private function showUser(): int
    {
        $userInput = $this->option('user');

        if (!$userInput) {
            $this->error('--user option is required for show-user action');
            return 1;
        }

        $user = $this->findUser($userInput);
        if (!$user) {
            return 1;
        }

        $this->info("User Details:");
        $this->line("ID: {$user->id}");
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line('');

        $this->line("<comment>Roles:</comment>");
        if ($user->roles->count() > 0) {
            foreach ($user->roles as $role) {
                $this->line("  - {$role->name}");
            }
        } else {
            $this->line("  No roles assigned");
        }
        $this->line('');

        $this->line("<comment>Permissions:</comment>");
        $permissions = $user->getAllPermissions();
        if ($permissions->count() > 0) {
            foreach ($permissions as $permission) {
                $this->line("  - {$permission->name}");
            }
        } else {
            $this->line("  No permissions");
        }
        
        return 0;
    }

    /**
     * Find user by ID or email
     */
    private function findUser($input)
    {
        if (is_numeric($input)) {
            $user = User::find($input);
        } else {
            $user = User::where('email', $input)->first();
        }

        if (!$user) {
            $this->error("User not found: {$input}");
            return null;
        }

        return $user;
    }
}

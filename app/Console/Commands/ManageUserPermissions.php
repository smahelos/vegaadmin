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
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list-roles':
                $this->listRoles();
                break;
            case 'list-permissions':
                $this->listPermissions();
                break;
            case 'assign-role':
                $this->assignRole();
                break;
            case 'remove-role':
                $this->removeRole();
                break;
            case 'show-user':
                $this->showUser();
                break;
            default:
                $this->error('Invalid action. Available actions: list-roles, list-permissions, assign-role, remove-role, show-user');
                return 1;
        }

        return 0;
    }

    /**
     * List all available roles and their permissions
     */
    private function listRoles()
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
    }

    /**
     * List all available permissions
     */
    private function listPermissions()
    {
        $permissions = Permission::all();
        
        $this->info('Available Permissions:');
        $this->line('');
        
        foreach ($permissions as $permission) {
            $this->line("- {$permission->name}");
        }
    }

    /**
     * Assign role to user
     */
    private function assignRole()
    {
        $userInput = $this->option('user');
        $roleName = $this->option('role');

        if (!$userInput || !$roleName) {
            $this->error('Both --user and --role options are required for assign-role action');
            return;
        }

        $user = $this->findUser($userInput);
        if (!$user) {
            return;
        }

        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $this->error("Role '{$roleName}' not found");
            return;
        }

        if ($user->hasRole($roleName)) {
            $this->warn("User already has role '{$roleName}'");
            return;
        }

        $user->assignRole($role);
        $this->info("Role '{$roleName}' assigned to user {$user->email}");
    }

    /**
     * Remove role from user
     */
    private function removeRole()
    {
        $userInput = $this->option('user');
        $roleName = $this->option('role');

        if (!$userInput || !$roleName) {
            $this->error('Both --user and --role options are required for remove-role action');
            return;
        }

        $user = $this->findUser($userInput);
        if (!$user) {
            return;
        }

        if (!$user->hasRole($roleName)) {
            $this->warn("User does not have role '{$roleName}'");
            return;
        }

        $user->removeRole($roleName);
        $this->info("Role '{$roleName}' removed from user {$user->email}");
    }

    /**
     * Show user details with roles and permissions
     */
    private function showUser()
    {
        $userInput = $this->option('user');

        if (!$userInput) {
            $this->error('--user option is required for show-user action');
            return;
        }

        $user = $this->findUser($userInput);
        if (!$user) {
            return;
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

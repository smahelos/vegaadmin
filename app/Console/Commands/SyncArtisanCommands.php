<?php

namespace App\Console\Commands;

use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use App\Services\ArtisanCommandsService;
use Illuminate\Console\Command;

class SyncArtisanCommands extends Command
{
    protected $signature = 'artisan:sync-commands';
    protected $description = 'Synchronize artisan commands with the database';

    protected $commandsService;

    public function __construct(ArtisanCommandsService $commandsService)
    {
        parent::__construct();
        $this->commandsService = $commandsService;
    }

    public function handle()
    {
        $availableCommands = $this->commandsService->getAllCommands();
        $databaseCommands = ArtisanCommand::pluck('command')->toArray();
        
        // Set category for uncategorized commands
        $uncategorizedCategory = ArtisanCommandCategory::firstOrCreate(
            ['slug' => 'uncategorized'],
            [
                'name' => __('admin.artisan_commands.uncategorized'),
                'description' => __('admin.artisan_commands.uncategorized_description'),
                'is_active' => true
            ]
        );
        
        // Find new commands that are not in the database
        $newCommands = array_diff(array_keys($availableCommands), $databaseCommands);
        
        // Add new commands to the database
        foreach ($newCommands as $command) {
            $description = str_replace($command . ' - ', '', $availableCommands[$command]);
            
            ArtisanCommand::create([
                'name' => $command,
                'command' => $command,
                'description' => $description,
                'category_id' => $uncategorizedCategory->id,
                'is_active' => false,
            ]);
        }
        
        // Mark commands that are no longer available
        // as inactive in the database
        $missingCommands = array_diff($databaseCommands, array_keys($availableCommands));
        if (count($missingCommands) > 0) {
            ArtisanCommand::whereIn('command', $missingCommands)->update(['is_active' => false]);
        }
        
        // Clear the commands cache
        $this->commandsService->clearCommandsCache();
        
        $this->info('Synchronisation finished.');
        $this->info('New commands added: ' . count($newCommands));
        $this->info('Marked as inactive commands: ' . count($missingCommands));
        
        return 0;
    }
}

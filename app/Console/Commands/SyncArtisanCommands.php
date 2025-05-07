<?php

namespace App\Console\Commands;

use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use App\Services\ArtisanCommandsService;
use Illuminate\Console\Command;

class SyncArtisanCommands extends Command
{
    protected $signature = 'artisan:sync-commands';
    protected $description = 'Synchronizuje dostupné Artisan příkazy s databází';

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
        
        // Určení kategorie pro nové příkazy
        $uncategorizedCategory = ArtisanCommandCategory::firstOrCreate(
            ['slug' => 'uncategorized'],
            [
                'name' => __('admin.artisan_commands.uncategorized'),
                'description' => __('admin.artisan_commands.uncategorized_description'),
                'is_active' => true
            ]
        );
        
        // Najdeme nové příkazy
        $newCommands = array_diff(array_keys($availableCommands), $databaseCommands);
        
        // Přidáme nové příkazy do databáze
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
        
        // Označíme neexistující příkazy
        $missingCommands = array_diff($databaseCommands, array_keys($availableCommands));
        if (count($missingCommands) > 0) {
            ArtisanCommand::whereIn('command', $missingCommands)->update(['is_active' => false]);
        }
        
        // Vyčistíme cache
        $this->commandsService->clearCommandsCache();
        
        $this->info('Synchronizace dokončena.');
        $this->info('Přidáno nových příkazů: ' . count($newCommands));
        $this->info('Označeno neaktivních příkazů: ' . count($missingCommands));
        
        return 0;
    }
}

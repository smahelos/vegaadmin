<?php

namespace App\Services;

use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ArtisanCommandsService
{
    /**
     * Získá seznam všech dostupných Artisan příkazů.
     *
     * @param bool $onlyNames Vrátit pouze názvy příkazů bez popisů
     * @return array
     */
    public function getAllCommands(bool $onlyNames = false): array
    {
        // Ukládáme výsledek do cache, abychom nezatěžovali server
        return Cache::remember('artisan_commands_list', 60 * 60, function () use ($onlyNames) {
            $commands = [];
            
            // Získáme všechny registrované příkazy
            $allCommands = Artisan::all();
            
            // Seřadíme podle názvu
            ksort($allCommands);
            
            foreach ($allCommands as $name => $command) {
                // Přeskočíme interní příkazy
                if (substr($name, 0, 1) === '_') {
                    continue;
                }
                
                // Získáme popis příkazu
                $description = $command->getDescription();
                
                // Přidáme do seznamu
                if ($onlyNames) {
                    $commands[$name] = $name;
                } else {
                    $commands[$name] = $name . ($description ? ' - ' . $description : '');
                }
            }
            
            return $commands;
        });
    }

    /**
     * Získá seznam příkazů podle kategorie
     *
     * @param string|null $categorySlug Slug kategorie nebo null pro všechny kategorie
     * @param bool $withoutCategory Zahrnout příkazy bez kategorie
     * @return array
     */
    public function getCommandsByCategory(?string $categorySlug = null, bool $withoutCategory = false): array
    {
        $cacheKey = "artisan_commands_by_category:{$categorySlug}:{$withoutCategory}";

        return Cache::remember($cacheKey, 60 * 5, function () use ($categorySlug, $withoutCategory) {
            $query = ArtisanCommand::where('is_active', true);

            if ($categorySlug !== null) {
                $category = ArtisanCommandCategory::where('slug', $categorySlug)
                    ->where('is_active', true)
                    ->first();

                if (!$category) {
                    return [];
                }

                $query->where('category_id', $category->id);
            }

            $commands = $query->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $result = [];
            foreach ($commands as $command) {
                $result[$command->command] = $command->name . ' - ' . $command->description;
            }

            // Pokud chceme zahrnout příkazy bez kategorie
            if ($withoutCategory) {
                $allCommands = $this->getAllCommands();
                $categorizedCommands = ArtisanCommand::pluck('command')->toArray();

                foreach ($allCommands as $command => $description) {
                    if (!in_array($command, $categorizedCommands)) {
                        $result[$command] = $description;
                    }
                }
            }

            return $result;
        });
    }

    /**
     * Získá seznam všech kategorií
     * 
     * @param bool $onlyActive Vrátit pouze aktivní kategorie
     * @return array
     */
    public function getAllCategories(bool $onlyActive = true): array
    {
        $cacheKey = "artisan_command_categories:{$onlyActive}";

        return Cache::remember($cacheKey, 60 * 5, function () use ($onlyActive) {
            $query = ArtisanCommandCategory::query();
            
            if ($onlyActive) {
                $query->where('is_active', true);
            }
            
            $categories = $query->orderBy('name')->get();
            
            $result = [];
            foreach ($categories as $category) {
                $result[$category->slug] = $category->name;
            }
            
            return $result;
        });
    }

    /**
     * Vymaže cache s příkazy
     */
    public function clearCommandsCache(): void
    {
        Cache::forget('artisan_commands_list');
        
        // Vymaže cache pro všechny kategorie
        $categories = ArtisanCommandCategory::pluck('slug')->toArray();
        foreach ($categories as $slug) {
            Cache::forget("artisan_commands_by_category:{$slug}:0");
            Cache::forget("artisan_commands_by_category:{$slug}:1");
        }
        
        Cache::forget("artisan_commands_by_category::0");
        Cache::forget("artisan_commands_by_category::1");
        Cache::forget("artisan_command_categories:0");
        Cache::forget("artisan_command_categories:1");
    }
}

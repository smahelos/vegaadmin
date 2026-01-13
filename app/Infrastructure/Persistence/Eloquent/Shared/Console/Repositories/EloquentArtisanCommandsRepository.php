<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Console\Repositories;

use App\Application\Shared\Console\Contracts\ArtisanCommandsRepositoryInterface;
use App\Models\ArtisanCommandCategory;
use App\Models\ArtisanCommand;
use Illuminate\Support\Facades\Artisan;

class EloquentArtisanCommandsRepository implements ArtisanCommandsRepositoryInterface
{
    /**
     * Retrieve all registered Artisan commands.
     *
     * @param bool $onlyNames When true returns associative array [name=>name]; otherwise name=>"name - description"
     * @return array<string,string>
     */
    public function getAllCommands(bool $onlyNames = false): array
    {
        $commands = [];
        $allCommands = Artisan::all();
        ksort($allCommands);
        foreach ($allCommands as $name => $command) {
            if (substr($name, 0, 1) === '_') {
                continue; // Skip internal underscore-prefixed commands
            }
            $description = $command->getDescription();
            if ($onlyNames) {
                $commands[$name] = $name;
            } else {
                $commands[$name] = $name . ($description ? ' - ' . $description : '');
            }
        }
        return $commands;
    }

    /** {@inheritdoc} */
    public function getCommandsByCategory(?string $categorySlug = null, bool $withoutCategory = false): array
    {
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
        $commands = $query->orderBy('sort_order')->orderBy('name')->get();
        $result = [];
        foreach ($commands as $command) {
        $result[$command->command] = $command->name . ' - ' . $command->description;
        }
        if ($withoutCategory) {
            $allCommands = $this->getAllCommands();
            $categorizedCommands = ArtisanCommand::pluck('command')->toArray();
            foreach ($allCommands as $command => $description) {
                if (!in_array($command, $categorizedCommands, true)) {
                    $result[$command] = $description;
                }
            }
        }
        return $result;
        
    }

    /** {@inheritdoc} */
    public function getAllCategories(bool $onlyActive = true): array
    {
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
    }

    /** {@inheritdoc} */
    public function getAllCommandsWithDetails(): array
    {
        $commands = [];
        $allCommands = Artisan::all();
        ksort($allCommands);
        foreach ($allCommands as $name => $command) {
            if (substr($name, 0, 1) === '_') {
                continue;
            }
            $commands[$name] = [
                'name' => $name,
                'description' => $command->getDescription(),
                'signature' => $command->getSynopsis(),
            ];
        }
        return $commands;
    }
}

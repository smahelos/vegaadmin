<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Console\Repositories;

use App\Domain\Shared\Console\Contracts\ArtisanCommandsDtoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Shared\Console\Repositories\EloquentArtisanCommandsRepository;

/**
 * Eloquent implementation of Artisan Commands DTO repository.
 * Delegates to the underlying repository and returns structures required by service layer.
 */
class EloquentArtisanCommandsDtoRepository implements ArtisanCommandsDtoRepositoryInterface
{
    public function __construct(
        private readonly EloquentArtisanCommandsRepository $acRepository,
    ) {}

    /**
     * Retrieve all registered Artisan commands.
     *
     * @param bool $onlyNames When true returns associative array [name=>name]; otherwise name=>"name - description"
     * @return array<string,string>
     */
    public function getAllCommands(bool $onlyNames = false): array
    {
        // This method returns raw strings from Artisan::all(), not models
        return $this->acRepository->getAllCommands($onlyNames);
    }

    /** {@inheritdoc} */
    public function getCommandsByCategory(?string $categorySlug = null, bool $withoutCategory = false): array
    {
        // This method returns raw strings from repository, not models  
        return $this->acRepository->getCommandsByCategory($categorySlug, $withoutCategory);
    }

    /** {@inheritdoc} */
    public function getAllCategories(bool $onlyActive = true): array
    {
        // This method returns raw strings from repository, not models
        return $this->acRepository->getAllCategories($onlyActive);
    }

    /** {@inheritdoc} */
    public function getAllCommandsWithDetails(): array
    {
        // Underlying repository already returns the expected associative array:
        // [command => ['name' => string, 'description' => ?string, 'signature' => string]]
        // Do not map to DTOs here to keep the contract intact and avoid type errors.
        return $this->acRepository->getAllCommandsWithDetails();
    }
}

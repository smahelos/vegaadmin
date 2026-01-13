<?php

namespace App\Domain\Shared\Console\Services;

use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\Console\Contracts\ArtisanCommandsServiceInterface;
use App\Domain\Shared\Console\Contracts\ArtisanCommandsDtoRepositoryInterface;

class ArtisanCommandsService implements ArtisanCommandsServiceInterface
{
    public function __construct(
        protected ArtisanCommandsDtoRepositoryInterface $dtoRepository,
        protected CacheServiceInterface $cacheService
    )
    {
    }

    /**
     * Retrieve all registered Artisan commands.
     *
     * @param bool $onlyNames When true returns associative array [name=>name]; otherwise name=>"name - description"
     * @return array<string,string>
     */
    public function getAllCommands(bool $onlyNames = false): array
    {
        return $this->cacheService->remember('artisan_commands_list', function () use ($onlyNames) {
            return $this->dtoRepository->getAllCommands($onlyNames);
        }, 60 * 60);
    }

    /** {@inheritdoc} */
    public function getCommandsByCategory(?string $categorySlug = null, bool $withoutCategory = false): array
    {
        $cacheKey = "artisan_commands_by_category:{$categorySlug}:{$withoutCategory}";
        return $this->cacheService->remember($cacheKey, function () use ($categorySlug, $withoutCategory) {
            return $this->dtoRepository->getCommandsByCategory($categorySlug, $withoutCategory);
        }, 60 * 5);
    }

    /** {@inheritdoc} */
    public function getAllCategories(bool $onlyActive = true): array
    {
        $cacheKey = "artisan_command_categories:{$onlyActive}";
        return $this->cacheService->remember($cacheKey, function () use ($onlyActive) {
            return $this->dtoRepository->getAllCategories($onlyActive);
        }, 60 * 5);
    }

    /** {@inheritdoc} */
    public function clearCommandsCache(): void
    {
        $this->cacheService->forget('artisan_commands_list');
        $categories = $this->dtoRepository->getAllCategories(false);
        foreach (array_keys($categories) as $slug) {
            $this->cacheService->forget("artisan_commands_by_category:{$slug}:0");
            $this->cacheService->forget("artisan_commands_by_category:{$slug}:1");
        }
        $this->cacheService->forget("artisan_commands_by_category::0");
        $this->cacheService->forget("artisan_commands_by_category::1");
        $this->cacheService->forget('artisan_command_categories:0');
        $this->cacheService->forget('artisan_command_categories:1');
        $this->cacheService->forget('artisan_commands_details');
    }

    /** {@inheritdoc} */
    public function getAllCommandsWithDetails(): array
    {
        return $this->cacheService->remember('artisan_commands_details', function () {
            return $this->dtoRepository->getAllCommandsWithDetails();
        }, 60 * 60);
    }
}

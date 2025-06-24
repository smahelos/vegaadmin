<?php

namespace App\Contracts;

interface ArtisanCommandsServiceInterface
{
    /**
     * Get list of all available Artisan commands
     *
     * @param bool $onlyNames Return only command names without descriptions
     * @return array Array of commands
     */
    public function getAllCommands(bool $onlyNames = false): array;

    /**
     * Get commands filtered by category
     *
     * @param string|null $categorySlug Category slug to filter by
     * @param bool $withoutCategory Whether to get commands without category
     * @return array Array of filtered commands
     */
    public function getCommandsByCategory(?string $categorySlug = null, bool $withoutCategory = false): array;

    /**
     * Get list of all command categories
     *
     * @param bool $onlyActive Whether to return only active categories
     * @return array Array of categories
     */
    public function getAllCategories(bool $onlyActive = true): array;

    /**
     * Clear all commands cache
     *
     * @return void
     */
    public function clearCommandsCache(): void;

    /**
     * Get all commands with detailed information
     *
     * @return array Array of commands with full details
     */
    public function getAllCommandsWithDetails(): array;
}

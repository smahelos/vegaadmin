<?php

namespace App\Domain\Shared\Console\Contracts;

/**
 * Service for introspecting and organizing available Artisan console commands.
 * Provides utilities to fetch command lists, categories and invalidate related cache entries.
 */
interface ArtisanCommandsDtoRepositoryInterface
{
    /**
     * Get list of all available Artisan commands.
     * When $onlyNames is true returns mapping command => command.
     * Otherwise returns mapping command => "command - description" (description omitted if empty).
     *
     * @param bool $onlyNames Return only command names without descriptions.
     * @return array<string,string> Associative array of command identifiers to label.
     */
    public function getAllCommands(bool $onlyNames = false): array;

    /**
     * Get commands filtered by category. Optionally include commands not stored in any category.
     *
     * @param string|null $categorySlug Slug of category to filter (null = any category).
     * @param bool $withoutCategory Include also uncategorized commands (only names & descriptions).
     * @return array<string,string> Mapping command => label.
     */
    public function getCommandsByCategory(?string $categorySlug = null, bool $withoutCategory = false): array;

    /**
     * Get list of command categories (slug => name).
     *
     * @param bool $onlyActive Return only active categories.
     * @return array<string,string> Mapping category slug => category name.
     */
    public function getAllCategories(bool $onlyActive = true): array;

    /**
     * Get all commands with detailed information.
     *
     * @return array<string, array{name:string, description:?string, signature:string}> Mapping command => detail array.
     */
    public function getAllCommandsWithDetails(): array;
}

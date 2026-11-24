<?php

namespace App\Domain\Content\DTO;

class PageDTO
{
    public function __construct(
        public readonly int $id,
        /** @var array<string,string>|null */
        public readonly ?array $name,
        /** @var array<string,string>|null */
        public readonly ?array $slug,
        /** @var array<string,string>|null */
        public readonly ?array $description,
        public readonly bool $published,
        public readonly ?PageCategoryDTO $category = null,
    ) {}

    public function localizedName(string $locale): string
    {
        return self::localized($this->name, $locale);
    }

    public function localizedSlug(string $locale): string
    {
        return self::localized($this->slug, $locale);
    }

    public function localizedDescription(string $locale): string
    {
        return self::localized($this->description, $locale);
    }

    /**
     * An admin may fill only some locales; the fallback order matches Page::getName().
     *
     * @param  array<string, string|null>|null  $values
     */
    private static function localized(?array $values, string $locale): string
    {
        foreach (
            [
                $locale,
                'cs',
                'en',
            ] as $candidate
        ) {
            $value = $values[$candidate] ?? '';
            if ($value !== '') {
                return $value;
            }
        }

        foreach ($values ?? [] as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return '';
    }
}

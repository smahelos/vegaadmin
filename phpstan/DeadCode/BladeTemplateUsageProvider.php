<?php

declare(strict_types=1);

namespace Billex\PHPStan\DeadCode;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use ReflectionProperty;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;
use SplFileInfo;

/**
 * PHPStan does not analyse Blade templates, so a method only a template calls ($subscription->isActive(),
 * $entry->getFileUrl()) looks unused. The receiver's type is unknown in a template, so every public method
 * of an App class with a name a template calls counts as used.
 */
final class BladeTemplateUsageProvider extends ReflectionBasedMemberUsageProvider
{
    private const VIEWS_PATH = __DIR__ . '/../../resources/views';

    /** @var array<string, true>|null */
    private ?array $calledNames = null;

    /** @var array<string, true>|null */
    private ?array $readNames = null;

    protected function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        $isAppMethod = $method->isPublic() && str_starts_with($method->getDeclaringClass()->getName(), 'App\\');

        return $isAppMethod && isset($this->calledNames()[$method->getName()])
            ? VirtualUsageData::withNote('Called by a Blade template')
            : null;
    }

    protected function shouldMarkPropertyAsRead(ReflectionProperty $property): ?VirtualUsageData
    {
        $isAppProperty = $property->isPublic() && str_starts_with($property->getDeclaringClass()->getName(), 'App\\');
        if ($this->readNames === null) {
            $this->readNames = [];
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::VIEWS_PATH));
            foreach ($files as $file) {
                if (! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }
                preg_match_all(
                    '/->\\s*([A-Za-z_]\\w*)\\b(?!\\s*\\()/',
                    (string) file_get_contents($file->getPathname()),
                    $matches,
                );
                foreach ($matches[1] as $name) {
                    $this->readNames[$name] = true;
                }
            }
        }

        return $isAppProperty && isset($this->readNames[$property->getName()])
            ? VirtualUsageData::withNote('Read by a Blade template') : null;
    }

    /**
     * @return array<string, true>
     */
    private function calledNames(): array
    {
        if ($this->calledNames !== null) {
            return $this->calledNames;
        }

        $this->calledNames = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::VIEWS_PATH));

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            preg_match_all(
                '/(?:->|::)\s*([A-Za-z_]\w*)\s*\(/',
                (string) file_get_contents($file->getPathname()),
                $matches,
            );

            foreach ($matches[1] as $name) {
                $this->calledNames[$name] = true;
            }
        }

        return $this->calledNames;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Facades\File;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Strikethrough\Strikethrough;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Paths and class names quoted in the maintained documentation exist, so an agent never looks for code that was
 * renamed or deleted; what counts as a reference: `docs/DOCUMENTATION.md`, References to code.
 */
final class DocumentationReferencesTest extends TestCase
{
    private const DOCUMENTS = [
        'AGENTS.md',
        'README.md',
        '.claude/rules',
        '.claude/references',
        '.claude/skills',
        'docs',
    ];

    private const CLASS_DIRECTORIES = [
        'app',
        'database',
        'phpcs',
        'phpstan',
        'tests',
    ];

    // Versioned directories only: an ignored one such as dev_docs/ is missing in CI
    private const PATH_ROOTS = [
        '.claude',
        '.codex',
        '.github',
        'app',
        'architecture',
        'bootstrap',
        'config',
        'database',
        'docs',
        'lang',
        'phpcs',
        'phpstan',
        'public',
        'resources',
        'routes',
        'storage',
        'tests',
        'vendor',
    ];

    // Rules and skills name the files of .claude/ relative to it (`AGENTS.md`)
    private const PATH_ALIASES = [
        'references/' => '.claude/references/',
        'rules/' => '.claude/rules/',
        'skills/' => '.claude/skills/',
    ];

    // A file name alone is a file in the root or a rule
    private const FILE_NAME_PATTERN = '/^[\w.-]+\.(?:dockerfile|js|json|jsonc|lock|md|mjs|neon|php|sh|toml|xml|yaml|yml)$/';

    private const SHORT_CLASS_PATTERN = '/^((?=[A-Z]\w*[A-Z])[A-Z]\w*[a-z]\w*)(?:::\$?\w+(?:\(\))?)?(?:->\w+(?:\(\))?)?$/';

    private const NAMESPACED_CLASS_PATTERN = '/^\\\\?((?:[A-Za-z_]\w*\\\\)+[A-Za-z_]\w*)(?:::\$?\w+(?:\(\))?)?(?:->\w+(?:\(\))?)?$/';

    // A pattern (`app/Domain/<Context>`, `*.php`) names no single file
    private const PATTERN_CHARACTERS = '/[*{}<>$…\s]/u';

    /** @var list<string> */
    private array $classFiles = [];

    /** @var array<string, true> */
    private array $classNames = [];

    private MarkdownParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::CLASS_DIRECTORIES as $directory) {
            foreach ($this->files($directory) as $file) {
                if (str_ends_with($file, '.php')) {
                    $this->classFiles[] = $file;
                    $this->classNames[basename($file, '.php')] = true;
                }
            }
        }

        $environment = new Environment;
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $this->parser = new MarkdownParser($environment);
    }

    #[Test]
    public function every_quoted_path_and_class_exists(): void
    {
        $missing = [];

        foreach ($this->documents() as $document) {
            $markdown = File::get(
                base_path($document),
            );
            array_push(
                $missing,
                ...$this->missingReferences($document, $markdown),
            );
        }

        $this->assertSame(
            [],
            $missing,
            "Documentation refers to code or files that do not exist; fix the reference, or write a planned or removed name without backticks:\n"
                . implode("\n", $missing),
        );
    }

    #[Test]
    public function reports_each_kind_of_missing_reference(): void
    {
        $markdown = <<<'MD'
            Existing: `app/Models`, `rules/php.md`, `phpstan.neon`, `php.md`, `EntityLimit`, `InvoiceStatus::PAID->value`,
            `Admin\InvoiceCrudController`, `App\Models`, `Illuminate\Testing\PendingCommand`, `DateTimeImmutable`,
            `app/Domain/<Context>`, [policy](DOCUMENTATION.md), [web](https://example.com).

            Missing: `app/Gone.php`, `rules/gone.md`, `gone.neon`, `GoneService`, `QRGoneService`, `Admin\GoneController`,
            `PendingCommand`, [gone](gone.md).

            ~~Struck `StruckGoneService`~~

            ```php
            $quoted = '`CodeBlockGoneService`';
            ```
            MD;

        $this->assertSame(
            [
                'docs/example.md:5 `app/Gone.php`',
                'docs/example.md:5 `rules/gone.md`',
                'docs/example.md:5 `gone.neon`',
                'docs/example.md:5 `GoneService`',
                'docs/example.md:5 `QRGoneService`',
                'docs/example.md:5 `Admin\GoneController`',
                'docs/example.md:5 `PendingCommand`',
                'docs/example.md:5 (gone.md)',
            ],
            $this->missingReferences('docs/example.md', $markdown),
        );
    }

    /**
     * @return list<string> "document:line `reference`" for each reference that does not resolve
     */
    private function missingReferences(string $document, string $markdown): array
    {
        $missing = [];
        $root = $this->parser->parse($markdown);

        foreach ($root->iterator() as $node) {
            if ($this->isStruckThrough($node)) {
                continue;
            }

            if (
                $node instanceof Code
                && !$this->quoteExists(
                    trim($node->getLiteral()),
                )
            ) {
                $missing[] = $this->location($document, $node) . " `{$node->getLiteral()}`";
            }

            if ($node instanceof Link && !$this->linkExists($document, $node->getUrl())) {
                $missing[] = $this->location($document, $node) . " ({$node->getUrl()})";
            }
        }

        return $missing;
    }

    private function quoteExists(string $quote): bool
    {
        if (preg_match(self::PATTERN_CHARACTERS, $quote) === 1) {
            return true;
        }

        foreach (self::PATH_ALIASES as $alias => $directory) {
            if (str_starts_with($quote, $alias)) {
                return file_exists(
                    base_path(
                        $directory . substr(
                            $quote,
                            strlen($alias),
                        ),
                    ),
                );
            }
        }

        $firstSegment = strstr(
            $quote,
            '/',
            true,
        );
        if (
            $firstSegment !== false
            && in_array(
                $firstSegment,
                self::PATH_ROOTS,
                true,
            )
        ) {
            // `app/Foo.php::method()` and `app/Foo.php:42` point into the file
            $path = (string) preg_replace(
                '/::.*|:\d+$/',
                '',
                $quote,
            );

            return file_exists(
                base_path(
                    rtrim($path, '.:'),
                ),
            );
        }

        if (preg_match(self::FILE_NAME_PATTERN, $quote) === 1) {
            return file_exists(
                base_path($quote),
            ) || file_exists(
                base_path('.claude/rules/' . $quote),
            );
        }

        if (
            preg_match(
                self::SHORT_CLASS_PATTERN,
                $quote,
                $match,
            ) === 1
        ) {
            return isset($this->classNames[$match[1]]) || $this->isBuiltIn($match[1]);
        }

        if (
            preg_match(
                self::NAMESPACED_CLASS_PATTERN,
                $quote,
                $match,
            ) === 1
        ) {
            return $this->namespacedNameExists($match[1]);
        }

        return true;
    }

    private function isBuiltIn(string $name): bool
    {
        return class_exists($name, false) || interface_exists($name, false);
    }

    private function namespacedNameExists(string $name): bool
    {
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            if ($loader->findFile($name) !== false) {
                return true;
            }

            // A namespace (`App\Models`, `Illuminate\Http`) exists as the directory of its PSR-4 prefix
            foreach ($loader->getPrefixesPsr4() as $prefix => $directories) {
                if (!str_starts_with($name . '\\', $prefix)) {
                    continue;
                }

                $subdirectory = str_replace(
                    '\\',
                    '/',
                    substr(
                        $name . '\\',
                        strlen($prefix),
                    ),
                );

                foreach ($directories as $directory) {
                    if (is_dir($directory . '/' . $subdirectory)) {
                        return true;
                    }
                }
            }
        }

        // A project class written from its context down (`Admin\InvoiceCrudController`)
        $suffix = '/' . str_replace(
            '\\',
            '/',
            $name,
        ) . '.php';

        foreach ($this->classFiles as $file) {
            if (str_ends_with($file, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function linkExists(string $document, string $url): bool
    {
        if (preg_match('/^(?:[a-z]+:|#)/', $url) === 1) {
            return true;
        }

        $path = strtok($url, '#');

        return file_exists(
            base_path(
                dirname($document) . '/' . urldecode($path),
            ),
        );
    }

    private function isStruckThrough(Node $node): bool
    {
        for ($parent = $node->parent(); $parent !== null; $parent = $parent->parent()) {
            if ($parent instanceof Strikethrough) {
                return true;
            }
        }

        return false;
    }

    private function location(string $document, Node $node): string
    {
        $block = $node;
        while (!$block instanceof AbstractBlock || $block->getStartLine() === null) {
            $block = $block->parent();
        }

        return $document . ':' . $block->getStartLine();
    }

    /**
     * @return list<string> paths relative to the project root
     */
    private function documents(): array
    {
        $documents = [];

        foreach (self::DOCUMENTS as $entry) {
            if (
                is_file(
                    base_path($entry),
                )
            ) {
                $documents[] = $entry;

                continue;
            }

            foreach ($this->files($entry) as $file) {
                if (str_ends_with($file, '.md')) {
                    $documents[] = $file;
                }
            }
        }

        return $documents;
    }

    /**
     * @return list<string> paths relative to the project root
     */
    private function files(string $directory): array
    {
        // Git's inventory, not a walk of the disk: ddd:guard tests create and delete directories in app/ meanwhile
        $git = new Process(
            [
                'git',
                'ls-files',
                '-z',
                '--cached',
                '--others',
                '--exclude-standard',
                '--',
                $directory,
            ],
            base_path(),
        );
        $git->mustRun();

        return array_values(
            array_filter(
                explode("\0", $git->getOutput()),
            ),
        );
    }
}

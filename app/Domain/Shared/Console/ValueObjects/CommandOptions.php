<?php

declare(strict_types=1);

namespace App\Domain\Shared\Console\ValueObjects;

use App\Domain\Shared\Console\Exceptions\InvalidCommandOptionsException;

/**
 * Options of a scheduled command, such as `--days-before=3 --force`. The scheduler hands the command to a shell
 * and does not escape a value that starts with `--`, so only options with a plain name and a value without
 * spaces, quotes or shell characters are accepted.
 */
final readonly class CommandOptions
{
    private const OPTION = '/^--([a-z][a-z0-9-]*)(?:=([A-Za-z0-9_.,:-]+))?$/';

    /**
     * @param  list<string>  $options  each option as written, `--name` or `--name=value`
     */
    private function __construct(public array $options) {}

    /**
     * @throws InvalidCommandOptionsException
     */
    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return new self([]);
        }

        $options = preg_split('/\s+/', $trimmed);
        foreach ($options as $option) {
            if (preg_match(self::OPTION, $option) !== 1) {
                throw new InvalidCommandOptionsException('Invalid command option: ' . $option);
            }
        }

        return new self($options);
    }

    /**
     * The options as Artisan's array input takes them: the value by `--name`, `true` for a flag.
     *
     * @return array<string, string|true>
     */
    public function input(): array
    {
        $input = [];
        foreach ($this->options as $option) {
            preg_match(
                self::OPTION,
                $option,
                $matches,
            );
            $input['--' . $matches[1]] = $matches[2] ?? true;
        }

        return $input;
    }
}

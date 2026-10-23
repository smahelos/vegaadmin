<?php

declare(strict_types=1);

namespace Billex\Sniffs\Arrays;

use Billex\ListLayout;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractArraySniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * An array with three or more items puts each item on its own line. Destructuring is one
 * assignment rather than a list of values, so it stays on one line.
 */
final class MultiLineArraySniff extends AbstractArraySniff
{
    private const MESSAGE = 'An array with %s or more items puts each item on its own line';

    protected function processSingleLineArray(
        File $phpcsFile,
        int $stackPtr,
        int $arrayStart,
        int $arrayEnd,
        array $indices,
    ): void {
        $this->check(
            $phpcsFile,
            $arrayStart,
            $arrayEnd,
            $indices,
        );
    }

    protected function processMultiLineArray(
        File $phpcsFile,
        int $stackPtr,
        int $arrayStart,
        int $arrayEnd,
        array $indices,
    ): void {
        $this->check(
            $phpcsFile,
            $arrayStart,
            $arrayEnd,
            $indices,
        );
    }

    /**
     * @param  list<array{index_start?: int, value_start: int}>  $indices
     */
    private function check(
        File $file,
        int $arrayStart,
        int $arrayEnd,
        array $indices,
    ): void {
        if ($this->isDestructuring(
            $file,
            $arrayStart,
            $arrayEnd,
        )) {
            return;
        }

        $itemStarts = array_map(
            fn (array $index): int => $index['index_start'] ?? $index['value_start'],
            $indices,
        );

        (new ListLayout)->check(
            $file,
            $arrayStart,
            $arrayEnd,
            $itemStarts,
            self::MESSAGE,
        );
    }

    private function isDestructuring(
        File $file,
        int $arrayStart,
        int $arrayEnd,
    ): bool {
        $tokens = $file->getTokens();
        $next = $file->findNext(
            Tokens::EMPTY_TOKENS,
            $arrayEnd + 1,
            null,
            true,
        );
        $previous = $file->findPrevious(
            Tokens::EMPTY_TOKENS,
            $arrayStart - 1,
            null,
            true,
        );

        return ($next !== false && $tokens[$next]['code'] === T_EQUAL)
            || ($previous !== false && $tokens[$previous]['code'] === T_AS);
    }
}

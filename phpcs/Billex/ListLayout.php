<?php

declare(strict_types=1);

namespace Billex;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Puts each item of a bracketed list on its own line once the list has three or more items
 * (rules/php.md, Formatting).
 *
 * The fixer only breaks lines; Pint sets the final indentation.
 */
final class ListLayout
{
    public const MIN_ITEMS = 3;

    private const INDENT = '    ';

    /**
     * @param  list<int>  $itemStarts  first token of each item
     */
    public function check(
        File $file,
        int $opener,
        int $closer,
        array $itemStarts,
        string $message,
    ): void {
        if (count($itemStarts) < self::MIN_ITEMS) {
            return;
        }

        $tokens = $file->getTokens();

        // The opener or comma that shares a line with the item following it.
        $sharedSeparators = [];
        foreach ($itemStarts as $itemStart) {
            $separator = $file->findPrevious(
                Tokens::EMPTY_TOKENS,
                $itemStart - 1,
                null,
                true,
            );

            if ($tokens[$separator]['line'] === $tokens[$itemStart]['line']) {
                $sharedSeparators[] = $separator;
            }
        }

        $lastContent = $file->findPrevious(
            Tokens::EMPTY_TOKENS,
            $closer - 1,
            null,
            true,
        );
        $closerShared = $tokens[$lastContent]['line'] === $tokens[$closer]['line'];

        if ($sharedSeparators === [] && ! $closerShared) {
            return;
        }

        $fix = $file->addFixableError(
            $message,
            $opener,
            'ItemsOnSharedLine',
            [self::MIN_ITEMS],
        );

        if (! $fix) {
            return;
        }

        $indent = $this->lineIndent($file, $opener);

        $file->fixer->beginChangeset();

        foreach ($sharedSeparators as $separator) {
            $this->breakLineAfter(
                $file,
                $separator,
                '',
                $indent . self::INDENT,
            );
        }

        if ($closerShared) {
            $comma = $tokens[$lastContent]['code'] === T_COMMA ? '' : ',';
            $this->breakLineAfter(
                $file,
                $lastContent,
                $comma,
                $indent,
            );
        }

        $file->fixer->endChangeset();
    }

    private function breakLineAfter(
        File $file,
        int $pointer,
        string $prefix,
        string $indent,
    ): void {
        $tokens = $file->getTokens();

        if ($tokens[$pointer + 1]['code'] === T_WHITESPACE) {
            $file->fixer->replaceToken($pointer + 1, '');
        }

        $file->fixer->addContent($pointer, $prefix . "\n" . $indent);
    }

    private function lineIndent(File $file, int $pointer): string
    {
        $tokens = $file->getTokens();
        $line = $tokens[$pointer]['line'];

        while ($pointer > 0 && $tokens[$pointer - 1]['line'] === $line) {
            $pointer--;
        }

        return $tokens[$pointer]['code'] === T_WHITESPACE ? $tokens[$pointer]['content'] : '';
    }
}

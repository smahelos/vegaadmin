<?php

declare(strict_types=1);

namespace Billex\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\TernaryOperatorHelper;

final class ShortTernarySniff implements Sniff
{
    private const MAX_LENGTH = 120;

    /** @return list<int|string> */
    public function register(): array
    {
        return [T_INLINE_THEN];
    }

    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $start = TernaryOperatorHelper::getStartPointer($phpcsFile, $stackPtr);
        $else = TernaryOperatorHelper::getElsePointer($phpcsFile, $stackPtr);
        $end = TernaryOperatorHelper::getEndPointer(
            $phpcsFile,
            $stackPtr,
            $else,
        );
        $text = $phpcsFile->getTokensAsString($start, $end - $start + 1);
        $nested = $phpcsFile->findNext(
            T_INLINE_THEN,
            $start,
            $end + 1,
        );

        if ($nested === $stackPtr) {
            $nested = $phpcsFile->findNext(
                T_INLINE_THEN,
                $stackPtr + 1,
                $end + 1,
            );
        }

        if (
            $tokens[$start]['line'] !== $tokens[$end]['line']
            || mb_strlen($text, 'UTF-8') > self::MAX_LENGTH
            || $nested !== false
        ) {
            $phpcsFile->addError(
                'Use if/else for a nested, multiline or longer than 120 characters ternary expression',
                $stackPtr,
                'ComplexTernary',
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Billex\Sniffs\Functions;

use Billex\ListLayout;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * A method, function, closure or arrow function with three or more parameters puts each
 * parameter on its own line.
 */
final class MultiLineSignatureSniff implements Sniff
{
    private const MESSAGE = 'A signature with %s or more parameters puts each parameter on its own line';

    public function register(): array
    {
        return [
            T_FUNCTION,
            T_CLOSURE,
            T_FN,
        ];
    }

    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (! isset($tokens[$stackPtr]['parenthesis_opener'], $tokens[$stackPtr]['parenthesis_closer'])) {
            return;
        }

        $opener = $tokens[$stackPtr]['parenthesis_opener'];
        $closer = $tokens[$stackPtr]['parenthesis_closer'];

        $itemStarts = [];
        $separator = $opener;
        foreach ($phpcsFile->getMethodParameters($stackPtr) as $parameter) {
            $itemStarts[] = $phpcsFile->findNext(
                Tokens::EMPTY_TOKENS,
                $separator + 1,
                $closer,
                true,
            );
            $separator = $parameter['comma_token'];

            if ($separator === false) {
                break;
            }
        }

        (new ListLayout)->check(
            $phpcsFile,
            $opener,
            $closer,
            $itemStarts,
            self::MESSAGE,
        );
    }
}

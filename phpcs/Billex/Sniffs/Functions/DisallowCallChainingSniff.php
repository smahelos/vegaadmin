<?php

declare(strict_types=1);

namespace Billex\Sniffs\Functions;

use Billex\CallSyntax;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\TokenHelper;

final class DisallowCallChainingSniff implements Sniff
{
    /** @return list<int|string> */
    public function register(): array
    {
        return [T_OPEN_PARENTHESIS];
    }

    public function process(File $phpcsFile, int $opener): void
    {
        if (!CallSyntax::isCall($phpcsFile, $opener)) {
            return;
        }
        $callee = TokenHelper::findPreviousEffective($phpcsFile, $opener - 1);
        $operator = CallSyntax::memberOperator($phpcsFile, $callee);
        if ($operator === null) {
            return;
        }
        $receiver = TokenHelper::findPreviousEffective($phpcsFile, $operator - 1);
        if ($receiver === null || !$this->isCallResult($phpcsFile, $receiver)) {
            return;
        }

        // Discarding a returned object is unsafe for immutable and terminal operations, so this has no auto-fix.
        $phpcsFile->addError(
            'Use separate statements instead of chained method calls; preserve each returned value that is needed',
            $operator,
            'ChainedCall',
        );
    }

    private function isCallResult(File $file, int $pointer): bool
    {
        $tokens = $file->getTokens();
        while ($pointer !== null) {
            if ($tokens[$pointer]['code'] === T_CLOSE_PARENTHESIS) {
                $opener = $tokens[$pointer]['parenthesis_opener'];
                if (CallSyntax::isCall($file, $opener)) {
                    $callee = TokenHelper::findPreviousEffective($file, $opener - 1);
                    $beforeCallee = TokenHelper::findPreviousEffective($file, $callee - 1);

                    return $beforeCallee === null || $tokens[$beforeCallee]['code'] !== T_NEW;
                }
                $pointer = TokenHelper::findPreviousEffective($file, $pointer - 1);

                continue;
            }
            if ($tokens[$pointer]['code'] === T_CLOSE_SQUARE_BRACKET) {
                $opener = $tokens[$pointer]['bracket_opener'];
                $pointer = TokenHelper::findPreviousEffective($file, $opener - 1);

                continue;
            }
            $operator = CallSyntax::memberOperator($file, $pointer);
            if ($operator === null) {
                return false;
            }
            $pointer = TokenHelper::findPreviousEffective($file, $operator - 1);
        }

        return false;
    }
}

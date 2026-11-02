<?php

declare(strict_types=1);

namespace Billex\Sniffs\Functions;

use Billex\CallSyntax;
use Billex\ListLayout;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\TokenHelper;

final class ComplexCallLayoutSniff implements Sniff
{
    private const MAX_ARGUMENT_LENGTH = 60;

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

        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$opener]['parenthesis_closer'];
        $starts = [];
        $complex = false;
        $start = $opener + 1;
        for ($pointer = $start; $pointer <= $closer; $pointer++) {
            if ($pointer === $closer || $tokens[$pointer]['code'] === T_COMMA) {
                $first = $phpcsFile->findNext(
                    Tokens::EMPTY_TOKENS,
                    $start,
                    $pointer,
                    true,
                );
                if ($first !== false) {
                    $starts[] = $first;
                    $complex = $complex || $this->isComplex(
                        $phpcsFile,
                        $first,
                        $pointer,
                    );
                }
                $start = $pointer + 1;

                continue;
            }

            $end = $tokens[$pointer]['parenthesis_closer']
                ?? $tokens[$pointer]['bracket_closer']
                ?? null;
            // An arrow's scope_closer can be the argument separator itself.
            if ($tokens[$pointer]['code'] === T_OPEN_CURLY_BRACKET) {
                $end = $tokens[$pointer]['scope_closer'] ?? $end;
            }
            if ($end !== null && $end < $closer) {
                $pointer = $end;
            }
        }

        $minimumItems = ListLayout::MIN_ITEMS;
        $message = 'A call with three or more arguments puts each argument on its own line';
        if ($complex) {
            $minimumItems = 1;
            $message = 'A call with a complex argument puts each argument on its own line';
        }

        (new ListLayout)->check(
            $phpcsFile,
            $opener,
            $closer,
            $starts,
            $message,
            $minimumItems,
        );
    }

    private function isComplex(
        File $file,
        int $start,
        int $end,
    ): bool {
        $tokens = $file->getTokens();
        $text = '';
        for ($pointer = $start; $pointer < $end; $pointer++) {
            if ($tokens[$pointer]['code'] === T_WHITESPACE) {
                $text .= ' ';
            } else {
                $text .= $tokens[$pointer]['content'];
            }
        }
        if (
            mb_strlen(
                trim($text),
                'UTF-8',
            ) > self::MAX_ARGUMENT_LENGTH
        ) {
            return true;
        }

        for ($pointer = $start; $pointer < $end; $pointer++) {
            if ($tokens[$pointer]['code'] !== T_OPEN_PARENTHESIS) {
                continue;
            }

            if (!CallSyntax::isCall($file, $pointer)) {
                continue;
            }

            $first = TokenHelper::findNextEffective($file, $pointer + 1);
            if ($first !== $tokens[$pointer]['parenthesis_closer']) {
                return true;
            }
        }

        return false;
    }
}

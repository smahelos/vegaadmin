<?php

declare(strict_types=1);

namespace Billex\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\TernaryOperatorHelper;

final class ShortTernarySniff implements Sniff
{
    private const MAX_LENGTH = 120;

    private const EXPRESSION_BOUNDARIES = Tokens::ASSIGNMENT_TOKENS + [
        T_CASE => T_CASE,
        T_CLOSE_CURLY_BRACKET => T_CLOSE_CURLY_BRACKET,
        T_COLON => T_COLON,
        T_COMMA => T_COMMA,
        T_DOUBLE_ARROW => T_DOUBLE_ARROW,
        T_ECHO => T_ECHO,
        T_FN_ARROW => T_FN_ARROW,
        T_MATCH_ARROW => T_MATCH_ARROW,
        T_OPEN_CURLY_BRACKET => T_OPEN_CURLY_BRACKET,
        T_OPEN_PARENTHESIS => T_OPEN_PARENTHESIS,
        T_OPEN_SHORT_ARRAY => T_OPEN_SHORT_ARRAY,
        T_OPEN_SQUARE_BRACKET => T_OPEN_SQUARE_BRACKET,
        T_OPEN_TAG => T_OPEN_TAG,
        T_OPEN_TAG_WITH_ECHO => T_OPEN_TAG_WITH_ECHO,
        T_RETURN => T_RETURN,
        T_SEMICOLON => T_SEMICOLON,
        T_THROW => T_THROW,
    ];

    private const EXPRESSION_ENDS = [
        T_CLOSE_CURLY_BRACKET => T_CLOSE_CURLY_BRACKET,
        T_CLOSE_PARENTHESIS => T_CLOSE_PARENTHESIS,
        T_CLOSE_SHORT_ARRAY => T_CLOSE_SHORT_ARRAY,
        T_CLOSE_SQUARE_BRACKET => T_CLOSE_SQUARE_BRACKET,
        T_CLOSE_TAG => T_CLOSE_TAG,
        T_COLON => T_COLON,
        T_COMMA => T_COMMA,
        T_DOUBLE_ARROW => T_DOUBLE_ARROW,
        T_MATCH_ARROW => T_MATCH_ARROW,
        T_SEMICOLON => T_SEMICOLON,
    ];

    /** @return list<int|string> */
    public function register(): array
    {
        return [T_INLINE_THEN];
    }

    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $start = $this->expressionStart($phpcsFile, $stackPtr);
        $else = TernaryOperatorHelper::getElsePointer($phpcsFile, $stackPtr);
        $end = $this->expressionEnd($phpcsFile, $else);
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

    /** Slevomat's start pointer runs past compound assignments and match arrows, and stops inside closed parentheses. */
    private function expressionStart(File $phpcsFile, int $stackPtr): int
    {
        $tokens = $phpcsFile->getTokens();
        $pointer = $stackPtr - 1;
        while (!isset(self::EXPRESSION_BOUNDARIES[$tokens[$pointer]['code']])) {
            $pointer = match ($tokens[$pointer]['code']) {
                T_CLOSE_PARENTHESIS => $tokens[$pointer]['parenthesis_opener'],
                T_CLOSE_SHORT_ARRAY, T_CLOSE_SQUARE_BRACKET => $tokens[$pointer]['bracket_opener'],
                default => $pointer,
            } - 1;
        }

        return $phpcsFile->findNext(
            Tokens::EMPTY_TOKENS,
            $pointer + 1,
            null,
            true,
        );
    }

    /** Slevomat's end pointer stops at a coalesce, at a comma after closed parentheses and at a closure's return type, all inside the alternative, and runs past the brace closing a match. */
    private function expressionEnd(File $phpcsFile, int $else): int
    {
        $tokens = $phpcsFile->getTokens();
        $pointer = $else + 1;
        while (isset($tokens[$pointer]) && !isset(self::EXPRESSION_ENDS[$tokens[$pointer]['code']])) {
            $pointer = match ($tokens[$pointer]['code']) {
                T_CLOSURE => $tokens[$pointer]['scope_closer'],
                T_FN => $tokens[$pointer]['scope_opener'],
                T_OPEN_PARENTHESIS => $tokens[$pointer]['parenthesis_closer'],
                T_OPEN_CURLY_BRACKET, T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET => $tokens[$pointer]['bracket_closer'],
                default => $pointer,
            } + 1;
        }

        return $phpcsFile->findPrevious(
            Tokens::EMPTY_TOKENS,
            $pointer - 1,
            null,
            true,
        );
    }
}

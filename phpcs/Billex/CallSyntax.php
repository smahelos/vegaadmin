<?php

declare(strict_types=1);

namespace Billex;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\TokenHelper;

final class CallSyntax
{
    public static function isCall(File $file, int $opener): bool
    {
        $tokens = $file->getTokens();
        if (isset($tokens[$opener]['parenthesis_owner'])) {
            $owner = $tokens[$opener]['parenthesis_owner'];
            if (
                in_array(
                    $tokens[$owner]['code'],
                    [
                        T_FUNCTION,
                        T_CLOSURE,
                        T_FN,
                    ],
                    true,
                )
            ) {
                return false;
            }
        }

        $callee = TokenHelper::findPreviousEffective($file, $opener - 1);
        if ($callee === null) {
            return false;
        }

        if ($tokens[$callee]['code'] === T_CLOSE_CURLY_BRACKET) {
            $operator = self::memberOperator($file, $callee);

            return $operator !== null;
        }

        return in_array(
            $tokens[$callee]['code'],
            [
                ...TokenHelper::NAME_TOKEN_CODES,
                ...TokenHelper::CLASS_KEYWORD_CODES,
                T_ANON_CLASS,
                T_VARIABLE,
                T_CLOSE_PARENTHESIS,
                T_CLOSE_SQUARE_BRACKET,
            ],
            true,
        );
    }

    public static function memberOperator(File $file, int $member): ?int
    {
        $tokens = $file->getTokens();
        if ($tokens[$member]['code'] === T_CLOSE_CURLY_BRACKET) {
            if (!isset($tokens[$member]['bracket_opener'])) {
                return null;
            }
            $member = $tokens[$member]['bracket_opener'];
        }
        $operator = TokenHelper::findPreviousEffective($file, $member - 1);
        if ($operator === null) {
            return null;
        }
        if (
            !in_array(
                $tokens[$operator]['code'],
                [
                    T_OBJECT_OPERATOR,
                    T_NULLSAFE_OBJECT_OPERATOR,
                    T_DOUBLE_COLON,
                ],
                true,
            )
        ) {
            return null;
        }

        return $operator;
    }
}

<?php

declare(strict_types=1);

namespace Billex\Sniffs\ControlStructures;

use Billex\CallSyntax;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\IndentationHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Sniffs\ControlStructures\AbstractLineCondition;

final class MultiLineConditionSniff extends AbstractLineCondition
{
    public function process(File $phpcsFile, int $controlStructurePointer): void
    {
        if ($this->shouldBeSkipped($phpcsFile, $controlStructurePointer)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$controlStructurePointer]['parenthesis_opener'];
        $closer = $tokens[$controlStructurePointer]['parenthesis_closer'];
        $groups = $this->conditionGroups(
            $phpcsFile,
            $opener,
            0,
        );
        if ($groups === []) {
            return;
        }

        if ($tokens[$opener]['line'] === $tokens[$closer]['line']) {
            $line = $this->getLineStart($phpcsFile, $opener)
                . $this->getCondition(
                    $phpcsFile,
                    $opener,
                    $closer,
                )
                . $this->getLineEnd($phpcsFile, $closer);
            if (mb_strlen($line, 'UTF-8') <= 120) {
                return;
            }
        } elseif (!$this->hasSharedParts($phpcsFile, $groups)) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            sprintf(
                'Condition of "%s" should be split to more lines so each condition part is on its own line.',
                $this->getControlStructureName($phpcsFile, $controlStructurePointer),
            ),
            $controlStructurePointer,
            'RequiredMultiLineCondition',
        );
        if (!$fix) {
            return;
        }

        $indentation = IndentationHelper::getIndentation(
            $phpcsFile,
            TokenHelper::findFirstNonWhitespaceOnLine($phpcsFile, $controlStructurePointer),
        );
        $phpcsFile->fixer->beginChangeset();
        foreach ($groups as $group) {
            $innerIndentation = IndentationHelper::addIndentation(
                $phpcsFile,
                $indentation,
                $group['depth'] + 1,
            );
            $first = TokenHelper::findNextEffective($phpcsFile, $group['opener'] + 1);
            $this->whitespaceBefore(
                $phpcsFile,
                $first,
                $phpcsFile->eolChar . $innerIndentation,
            );
            $this->whitespaceBefore(
                $phpcsFile,
                $group['closer'],
                $phpcsFile->eolChar . IndentationHelper::addIndentation(
                    $phpcsFile,
                    $indentation,
                    $group['depth'],
                ),
            );
            foreach ($group['operators'] as $operator) {
                $this->whitespaceBefore(
                    $phpcsFile,
                    $operator,
                    $phpcsFile->eolChar . $innerIndentation,
                );
                $next = $phpcsFile->findNext(
                    T_WHITESPACE,
                    $operator + 1,
                    null,
                    true,
                );
                // Keep comments and their line breaks attached to the following operand.
                if (!isset(Tokens::COMMENT_TOKENS[$tokens[$next]['code']])) {
                    $this->whitespaceBefore(
                        $phpcsFile,
                        $next,
                        ' ',
                    );
                }
            }
        }
        $phpcsFile->fixer->endChangeset();
    }

    /** @return list<array{opener: int, closer: int, depth: int, operators: list<int>}> */
    private function conditionGroups(
        File $file,
        int $opener,
        int $depth,
    ): array {
        $tokens = $file->getTokens();
        $closer = $tokens[$opener]['parenthesis_closer'];
        $operators = [];
        $children = [];
        for ($pointer = $opener + 1; $pointer < $closer; $pointer++) {
            $code = $tokens[$pointer]['code'];
            if (isset(Tokens::BOOLEAN_OPERATORS[$code])) {
                $operators[] = $pointer;
            }

            // Boolean expressions inside operands are owned by their call, array or body.
            $end = $tokens[$pointer]['bracket_closer'] ?? null;
            if ($code === T_OPEN_CURLY_BRACKET) {
                $end = $tokens[$pointer]['scope_closer'] ?? $end;
            }
            if ($code === T_FN) {
                $end = $tokens[$pointer]['scope_closer'] - 1;
            }
            if ($code === T_OPEN_PARENTHESIS) {
                $end = $tokens[$pointer]['parenthesis_closer'];
                $previous = TokenHelper::findPreviousEffective($file, $pointer - 1);
                if (
                    !CallSyntax::isCall($file, $pointer)
                    && !isset($tokens[$pointer]['parenthesis_owner'])
                    && !in_array(
                        $tokens[$previous]['code'],
                        [
                            T_ISSET,
                            T_EMPTY,
                            T_EVAL,
                            T_EXIT,
                            T_ARRAY,
                            T_LIST,
                            T_MATCH,
                        ],
                        true,
                    )
                ) {
                    $children = array_merge(
                        $children,
                        $this->conditionGroups(
                            $file,
                            $pointer,
                            $depth + 1,
                        ),
                    );
                }
            }
            if ($end !== null && $end > $pointer && $end < $closer) {
                $pointer = $end;
            }
        }

        if ($operators === [] && $children === []) {
            return [];
        }

        return [
            [
                'opener' => $opener,
                'closer' => $closer,
                'depth' => $depth,
                'operators' => $operators,
            ],
            ...$children,
        ];
    }

    /** @param list<array{opener: int, closer: int, depth: int, operators: list<int>}> $groups */
    private function hasSharedParts(File $file, array $groups): bool
    {
        $tokens = $file->getTokens();
        foreach ($groups as $group) {
            $first = TokenHelper::findNextEffective($file, $group['opener'] + 1);
            $last = TokenHelper::findPreviousEffective($file, $group['closer'] - 1);
            if (
                $tokens[$group['opener']]['line'] === $tokens[$first]['line']
                || $this->endLine($file, $last) === $tokens[$group['closer']]['line']
            ) {
                return true;
            }
            foreach ($group['operators'] as $operator) {
                $previous = TokenHelper::findPreviousEffective($file, $operator - 1);
                $next = $file->findNext(
                    T_WHITESPACE,
                    $operator + 1,
                    null,
                    true,
                );
                if (
                    $this->endLine($file, $previous) === $tokens[$operator]['line']
                    || (
                        !isset(Tokens::COMMENT_TOKENS[$tokens[$next]['code']])
                        && $tokens[$operator]['line'] !== $tokens[$next]['line']
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function endLine(File $file, int $pointer): int
    {
        $tokens = $file->getTokens();

        return $tokens[$pointer]['line'] + substr_count(
            rtrim($tokens[$pointer]['content'], "\r\n"),
            "\n",
        );
    }

    private function whitespaceBefore(
        File $file,
        int $pointer,
        string $whitespace,
    ): void {
        $tokens = $file->getTokens();
        for ($previous = $pointer - 1; $tokens[$previous]['code'] === T_WHITESPACE; $previous--) {
            $file->fixer->replaceToken($previous, '');
        }
        $file->fixer->addContentBefore($pointer, $whitespace);
    }
}

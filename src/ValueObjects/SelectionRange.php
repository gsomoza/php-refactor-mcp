<?php

declare(strict_types=1);

namespace Somoza\PhpRefactorMcp\ValueObjects;

/**
 * Value object representing a selection range in a PHP file.
 */
final class SelectionRange
{
    public function __construct(
        public readonly int $startLine,
        public readonly int $startColumn,
        public readonly int $endLine,
        public readonly int $endColumn
    ) {}

    /**
     * Parse a selection range string in format "startLine:startColumn-endLine:endColumn".
     *
     * Supported formats:
     * - "startLine:startColumn-endLine:endColumn" (full format)
     * - "startLine-endLine" (line range only)
     * - "line:column" (single position)
     * - "line" (single line)
     *
     * @return self|null Returns SelectionRange on success, null on failure
     */
    public static function tryParse(string $selectionRange): ?self
    {
        if (preg_match('/^(\d+):(\d+)-(\d+):(\d+)$/', $selectionRange, $matches)) {
            // Full format: "startLine:startColumn-endLine:endColumn"
            return new self(
                (int) $matches[1],
                (int) $matches[2],
                (int) $matches[3],
                (int) $matches[4]
            );
        }

        if (preg_match('/^(\d+)-(\d+)$/', $selectionRange, $matches)) {
            // Line range only: "startLine-endLine"
            return new self(
                (int) $matches[1],
                0,
                (int) $matches[2],
                0
            );
        }

        if (preg_match('/^(\d+):(\d+)$/', $selectionRange, $matches)) {
            // Single position: "line:column"
            $line = (int) $matches[1];
            $column = (int) $matches[2];
            return new self($line, $column, $line, $column);
        }

        if (preg_match('/^(\d+)$/', $selectionRange, $matches)) {
            // Single line: "line"
            $line = (int) $matches[1];
            return new self($line, 0, $line, 0);
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Somoza\PhpParserMcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class ExtractVariableTool
{
    private ParserFactory $parserFactory;
    private Standard $printer;

    public function __construct()
    {
        $this->parserFactory = new ParserFactory();
        $this->printer = new Standard();
    }

    /**
     * Extract an expression into a named variable
     *
     * @param string $file Path to the PHP file
     * @param int $line Line number of the expression
     * @param int $column Column number of the expression
     * @param string $variableName Name for the new variable (with or without $ prefix)
     * @return array{success: bool, code?: string, file?: string, error?: string}
     */
    #[McpTool(
        name: 'extract_variable',
        description: 'Extract an expression into a named variable'
    )]
    public function extract(
        #[Schema(
            type: 'string',
            description: 'Path to the PHP file'
        )]
        string $file,
        #[Schema(
            type: 'integer',
            description: 'Line number of the expression'
        )]
        int $line,
        #[Schema(
            type: 'integer',
            description: 'Column number of the expression'
        )]
        int $column,
        #[Schema(
            type: 'string',
            description: 'Name for the new variable (with or without $ prefix)'
        )]
        string $variableName
    ): array {
        try {
            // Check if file exists
            if (!file_exists($file)) {
                return [
                    'success' => false,
                    'error' => "File not found: {$file}"
                ];
            }

            // Check if file is readable
            if (!is_readable($file)) {
                return [
                    'success' => false,
                    'error' => "File is not readable: {$file}"
                ];
            }

            // Read file contents
            $code = file_get_contents($file);
            if ($code === false) {
                return [
                    'success' => false,
                    'error' => "Failed to read file: {$file}"
                ];
            }

            // Normalize variable name (remove $ prefix if present)
            $variableName = ltrim($variableName, '$');

            if (empty($variableName)) {
                return [
                    'success' => false,
                    'error' => 'Variable name cannot be empty'
                ];
            }

            // Parse the code
            $parser = $this->parserFactory->createForNewestSupportedVersion();
            $ast = $parser->parse($code);

            if ($ast === null) {
                return [
                    'success' => false,
                    'error' => 'Failed to parse code: parser returned null'
                ];
            }

            // Find the expression at the specified line and column
            $expressionFinder = new ExpressionFinder($line, $column);
            $traverser = new NodeTraverser();
            $traverser->addVisitor($expressionFinder);
            $traverser->traverse($ast);

            $targetExpr = $expressionFinder->getExpression();
            $parentStmt = $expressionFinder->getParentStatement();

            if ($targetExpr === null) {
                return [
                    'success' => false,
                    'error' => "Could not find expression at line {$line}, column {$column}"
                ];
            }

            if ($parentStmt === null) {
                return [
                    'success' => false,
                    'error' => "Could not find parent statement for expression"
                ];
            }

            // Extract the expression
            $extractor = new ExpressionExtractor($targetExpr, $parentStmt, $variableName);
            $traverser = new NodeTraverser();
            $traverser->addVisitor($extractor);
            $ast = $traverser->traverse($ast);

            // Generate the modified code
            $modifiedCode = $this->printer->prettyPrintFile($ast);

            return [
                'success' => true,
                'code' => $modifiedCode,
                'file' => $file
            ];
        } catch (Error $e) {
            return [
                'success' => false,
                'error' => 'Parse error: ' . $e->getMessage()
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }
}

/**
 * NodeVisitor to find an expression at a specific line and column
 */
class ExpressionFinder extends NodeVisitorAbstract
{
    private int $targetLine;
    private int $targetColumn;
    private ?Expr $expression = null;
    private ?Node $parentStatement = null;
    private array $stmtStack = [];
    private ?Expr $bestMatch = null;

    public function __construct(int $targetLine, int $targetColumn)
    {
        $this->targetLine = $targetLine;
        $this->targetColumn = $targetColumn;
    }

    public function enterNode(Node $node): ?int
    {
        // Track statements
        if ($node instanceof Node\Stmt) {
            $this->stmtStack[] = $node;
        }

        // Look for expressions at the target location (but not assignments or variables)
        if ($node instanceof Expr && !($node instanceof Variable) && !($node instanceof Expr\Assign)) {
            if ($node->hasAttribute('startLine')) {
                $startLine = $node->getAttribute('startLine');
                
                // Match expressions on the target line
                if ($startLine === $this->targetLine) {
                    // Store the first match as best match
                    if ($this->bestMatch === null) {
                        $this->bestMatch = $node;
                        $this->parentStatement = !empty($this->stmtStack) ? end($this->stmtStack) : null;
                    }
                    // Prefer larger (less specific / outermost) expressions on the same line
                    elseif ($this->isLessSpecific($node, $this->bestMatch)) {
                        $this->bestMatch = $node;
                        $this->parentStatement = !empty($this->stmtStack) ? end($this->stmtStack) : null;
                    }
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        // Pop statements
        if ($node instanceof Node\Stmt) {
            if (!empty($this->stmtStack) && end($this->stmtStack) === $node) {
                array_pop($this->stmtStack);
            }
        }

        return null;
    }

    private function isLessSpecific(Node $node1, Node $node2): bool
    {
        // A node is less specific if it contains another node
        if (!$node1->hasAttribute('startFilePos') || !$node1->hasAttribute('endFilePos')) {
            return false;
        }
        if (!$node2->hasAttribute('startFilePos') || !$node2->hasAttribute('endFilePos')) {
            return true;
        }

        $start1 = $node1->getAttribute('startFilePos');
        $end1 = $node1->getAttribute('endFilePos');
        $start2 = $node2->getAttribute('startFilePos');
        $end2 = $node2->getAttribute('endFilePos');

        // Node1 is less specific if it contains node2
        return $start1 <= $start2 && $end1 >= $end2 && 
               !($start1 === $start2 && $end1 === $end2);
    }

    public function getExpression(): ?Expr
    {
        // Return the best match found
        return $this->bestMatch;
    }

    public function getParentStatement(): ?Node
    {
        return $this->parentStatement;
    }
}

/**
 * NodeVisitor to extract an expression into a variable
 */
class ExpressionExtractor extends NodeVisitorAbstract
{
    private Expr $targetExpr;
    private Node $parentStmt;
    private string $variableName;
    private bool $extracted = false;

    public function __construct(Expr $targetExpr, Node $parentStmt, string $variableName)
    {
        $this->targetExpr = $targetExpr;
        $this->parentStmt = $parentStmt;
        $this->variableName = $variableName;
    }

    public function leaveNode(Node $node)
    {
        // Replace the target expression with a variable reference
        if ($node === $this->targetExpr && !$this->extracted) {
            return new Variable($this->variableName);
        }

        // Insert the variable assignment before the parent statement
        if ($node === $this->parentStmt && !$this->extracted) {
            $this->extracted = true;
            
            // Create the assignment statement
            $assignment = new Expression(
                new Expr\Assign(
                    new Variable($this->variableName),
                    clone $this->targetExpr
                )
            );

            // Return an array to insert the assignment before the current statement
            return [
                $assignment,
                $node
            ];
        }

        return null;
    }
}

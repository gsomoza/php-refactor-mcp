<?php

declare(strict_types=1);

namespace GSomoza\PhpParserMcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class ParseTool
{
    private ParserFactory $parserFactory;
    private Standard $printer;

    public function __construct()
    {
        $this->parserFactory = new ParserFactory();
        $this->printer = new Standard();
    }

    /**
     * Parse PHP code and return the Abstract Syntax Tree (AST) representation
     *
     * @param string $code PHP code to parse
     * @return array{success: bool, ast?: string, nodeCount?: int, error?: string}
     */
    #[McpTool(
        name: 'parse_php',
        description: 'Parse PHP code and return the Abstract Syntax Tree (AST) representation'
    )]
    public function parse(
        #[Schema(
            type: 'string',
            description: 'PHP code to parse'
        )]
        string $code
    ): array {
        try {
            $parser = $this->parserFactory->createForNewestSupportedVersion();
            $ast = $parser->parse($code);

            if ($ast === null) {
                return [
                    'success' => false,
                    'error' => 'Failed to parse code: parser returned null'
                ];
            }

            // Convert AST to readable string representation
            $astString = $this->astToString($ast);

            return [
                'success' => true,
                'ast' => $astString,
                'nodeCount' => count($ast)
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

    /**
     * Convert AST nodes to a readable string representation
     */
    private function astToString(array $nodes): string
    {
        $output = [];
        foreach ($nodes as $node) {
            $output[] = $this->nodeToString($node, 0);
        }
        return implode("\n", $output);
    }

    /**
     * Convert a single node to string with indentation
     */
    private function nodeToString(object $node, int $depth): string
    {
        $indent = str_repeat('  ', $depth);
        $className = get_class($node);
        $shortName = substr($className, strrpos($className, '\\') + 1);
        
        $result = $indent . $shortName;
        
        // Add some useful properties for common node types
        if (property_exists($node, 'name') && is_object($node->name)) {
            if (method_exists($node->name, '__toString')) {
                $result .= ' (' . $node->name . ')';
            }
        } elseif (property_exists($node, 'name') && is_string($node->name)) {
            $result .= ' (' . $node->name . ')';
        }
        
        return $result;
    }
}

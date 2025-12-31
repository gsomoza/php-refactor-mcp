<?php

declare(strict_types=1);

namespace Somoza\PhpRefactorMcp\Tests\Tools;

use Somoza\PhpRefactorMcp\Tests\Support\FixtureBasedTestCase;
use Somoza\PhpRefactorMcp\Tools\ExtractVariableTool;

class ExtractVariableToolTest extends FixtureBasedTestCase
{
    private ExtractVariableTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tool = new ExtractVariableTool($this->filesystem);
    }

    protected function getToolName(): string
    {
        return 'ExtractVariableTool';
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    protected function executeTool(string $fixtureName, string $code, array $params): array
    {
        // Create a virtual file with the fixture code
        $file = $this->createFile('/test.php', $code);

        // Execute the tool with parameters from the fixture
        return $this->tool->extract(
            $file,
            $params['position'] ?? '1:1',
            $params['variableName'] ?? '$var'
        );
    }

    // Error cases - traditional test methods

    public function testExtractFileNotFound(): void
    {
        $result = $this->tool->extract('/nonexistent/file.php', '1:1', '$var');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('File not found', $result['error']);
    }

    public function testExtractEmptyVariableName(): void
    {
        $code = '<?php $x = 1 + 2;';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->extract($file, '1:12', '');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('cannot be empty', $result['error']);
    }

    public function testExtractSyntaxError(): void
    {
        $file = $this->createFile('/test.php', '<?php $x = ;');
        $result = $this->tool->extract($file, '1:12', '$var');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Parse error', $result['error']);
    }

    public function testExtractInvalidLineNumber(): void
    {
        $code = '<?php
$x = 1 + 2;
';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->extract($file, '999:1', '$var');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Could not find expression', $result['error']);
    }
}

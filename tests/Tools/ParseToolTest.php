<?php

declare(strict_types=1);

namespace Somoza\PhpRefactorMcp\Tests\Tools;

use Somoza\PhpRefactorMcp\Tests\Support\FilesystemTestCase;
use Somoza\PhpRefactorMcp\Tools\ParseTool;

class ParseToolTest extends FilesystemTestCase
{
    private ParseTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tool = new ParseTool($this->filesystem);
    }



    public function testParseSimpleCode(): void
    {
        $file = $this->createFile('/test.php', '<?php $x = 1 + 2;');
        $result = $this->tool->parse($file);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ast', $result);
        $this->assertArrayHasKey('nodeCount', $result);
        $this->assertGreaterThan(0, $result['nodeCount']);
        $this->assertEquals($file, $result['file']);
    }

    public function testParseClassDefinition(): void
    {
        $code = '<?php
class MyClass {
    private $property;

    public function myMethod() {
        return $this->property;
    }
}';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->parse($file);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ast', $result);
        $this->assertStringContainsString('Class_', $result['ast']);
    }

    public function testParseFunctionDefinition(): void
    {
        $code = '<?php
function calculateSum($a, $b) {
    return $a + $b;
}';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->parse($file);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ast', $result);
        $this->assertStringContainsString('Function_', $result['ast']);
    }

    public function testParsePhp71Code(): void
    {
        // PHP 7.1 features: nullable types, list destructuring
        $code = '<?php
function test(?string $param): ?int {
    [$a, $b] = [1, 2];
    return $a + $b;
}';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->parse($file);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ast', $result);
    }

    public function testParseSyntaxError(): void
    {
        $file = $this->createFile('/test.php', '<?php $x = ;'); // Syntax error: missing expression
        $result = $this->tool->parse($file);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Parse error', $result['error']);
    }

    public function testParseInvalidCode(): void
    {
        $file = $this->createFile('/test.php', 'not valid php code at all');
        $result = $this->tool->parse($file);

        // Text without PHP tags is treated as InlineHTML, which is valid
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ast', $result);
    }

    public function testParseEmptyCode(): void
    {
        $file = $this->createFile('/test.php', '');
        $result = $this->tool->parse($file);

        // Empty code results in empty AST (no statements)
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('nodeCount', $result);
        $this->assertEquals(0, $result['nodeCount']);
    }

    public function testParseVariableAssignment(): void
    {
        $code = '<?php
$name = "John";
$age = 30;
$isActive = true;
';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->parse($file);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ast', $result);
        $this->assertArrayHasKey('nodeCount', $result);
        $this->assertEquals(3, $result['nodeCount']);
    }

    public function testParseNamespaceAndUseStatements(): void
    {
        $code = '<?php
namespace MyApp\Services;

use MyApp\Models\User;
use MyApp\Repositories\UserRepository;

class UserService {
    private UserRepository $repository;
}
';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->parse($file);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ast', $result);
        $this->assertStringContainsString('Namespace_', $result['ast']);
    }

    public function testParseComplexExpression(): void
    {
        $code = '<?php
$result = ($a + $b) * ($c - $d) / $e;
';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->parse($file);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ast', $result);
    }

    public function testParseNonExistentFile(): void
    {
        $result = $this->tool->parse('/path/to/nonexistent/file.php');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('File not found', $result['error']);
    }

}

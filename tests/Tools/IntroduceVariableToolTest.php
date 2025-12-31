<?php

declare(strict_types=1);

namespace Somoza\PhpRefactorMcp\Tests\Tools;

use Somoza\PhpRefactorMcp\Tests\Support\FilesystemTestCase;
use Somoza\PhpRefactorMcp\Tools\IntroduceVariableTool;

class IntroduceVariableToolTest extends FilesystemTestCase
{
    private IntroduceVariableTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tool = new IntroduceVariableTool($this->filesystem);
    }



    public function testIntroduceSimpleExpression(): void
    {
        $code = '<?php
$result = 1 + 2;
';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, '2:11', '$sum');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('code', $result);
        $this->assertStringContainsString('$sum = 1 + 2', $result['code']);
        $this->assertStringContainsString('$result = $sum', $result['code']);

        // Snapshot test: verify full output and valid PHP
        $this->assertValidPhpSnapshot($result['code']);
    }

    public function testIntroduceExpressionWithoutDollarSign(): void
    {
        $code = '<?php
$result = 5 * 10;
';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, '2:11', 'product');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('$product = 5 * 10', $result['code']);
        $this->assertStringContainsString('$result = $product', $result['code']);

        // Snapshot test: verify full output and valid PHP
        $this->assertValidPhpSnapshot($result['code']);
    }

    public function testIntroduceExpressionInFunction(): void
    {
        $code = '<?php
function calculate() {
    return 10 + 20;
}';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, '3:12', '$sum');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('$sum = 10 + 20', $result['code']);
        $this->assertStringContainsString('return $sum', $result['code']);

        // Snapshot test: verify full output and valid PHP
        $this->assertValidPhpSnapshot($result['code']);
    }

    public function testIntroduceExpressionInMethod(): void
    {
        $code = '<?php
class MyClass {
    public function calculate() {
        $x = 5 * 3;
        return $x;
    }
}';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, '4:14', '$product');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('$product = 5 * 3', $result['code']);
        $this->assertStringContainsString('$x = $product', $result['code']);

        // Snapshot test: verify full output and valid PHP
        $this->assertValidPhpSnapshot($result['code']);
    }

    public function testIntroduceComplexExpression(): void
    {
        $code = '<?php
$total = ($a + $b) * ($c - $d);
';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, '2:10', '$intermediate');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('code', $result);
        // Should introduce a variable for an expression
        $this->assertStringContainsString('$intermediate', $result['code']);
        $this->assertStringContainsString('$total', $result['code']);

        // Snapshot test: verify full output and valid PHP
        $this->assertValidPhpSnapshot($result['code']);
    }

    public function testIntroduceMethodCall(): void
    {
        $code = '<?php
$result = $obj->method();
';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, '2:11', '$value');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('$value = $obj->method()', $result['code']);
        $this->assertStringContainsString('$result = $value', $result['code']);

        // Snapshot test: verify full output and valid PHP
        $this->assertValidPhpSnapshot($result['code']);
    }

    public function testIntroduceArrayAccess(): void
    {
        $code = '<?php
$value = $array[0];
';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, '2:10', '$element');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('$element = $array[0]', $result['code']);
        $this->assertStringContainsString('$value = $element', $result['code']);

        // Snapshot test: verify full output and valid PHP
        $this->assertValidPhpSnapshot($result['code']);
    }

    public function testIntroduceWithRangeSelection(): void
    {
        $code = '<?php
$result = 100 + 200;
';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, '2:11-2:18', '$sum');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('$sum', $result['code']);
        $this->assertStringContainsString('$result', $result['code']);

        // Snapshot test: verify full output and valid PHP
        $this->assertValidPhpSnapshot($result['code']);
    }

    public function testIntroduceFileNotFound(): void
    {
        $result = $this->tool->introduce('/nonexistent/file.php', '1:1', '$var');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('File not found', $result['error']);
    }

    public function testIntroduceEmptyVariableName(): void
    {
        $code = '<?php $x = 1 + 2;';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, '1:12', '');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('cannot be empty', $result['error']);
    }

    public function testIntroduceInvalidVariableName(): void
    {
        $code = '<?php $x = 1 + 2;';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, '1:12', '123invalid');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('invalid', $result['error']);
    }

    public function testIntroduceSyntaxError(): void
    {
        $file = $this->createFile('/test.php', '<?php $x = ;');
        $result = $this->tool->introduce($file, '1:12', '$var');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Parse error', $result['error']);
    }


    public function testIntroduceInvalidLineNumber(): void
    {
        $code = '<?php
$x = 1 + 2;
';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, '999:1', '$var');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Could not find expression', $result['error']);
    }

    public function testIntroduceInvalidRange(): void
    {
        $code = '<?php $x = 1 + 2;';
        $file = $this->createFile('/test.php', $code);
        $result = $this->tool->introduce($file, 'invalid-range', '$var');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Invalid selection range', $result['error']);
    }
}

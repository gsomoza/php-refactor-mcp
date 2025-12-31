<?php

declare(strict_types=1);

namespace Somoza\PhpRefactorMcp\Tests\Support;

/**
 * Base test case for fixture-based testing.
 *
 * This class provides infrastructure for discovering and running tests based on fixture files.
 * Fixtures are simple PHP files under tests/Fixtures/{ToolName}/{TestCase}.php
 *
 * Each fixture is a PHP file containing the code to be refactored.
 * Test parameters are parsed from special comment headers in the fixture file.
 *
 * The output is validated using snapshot testing - no custom assertions needed.
 * Error cases should be implemented as regular test methods in the test class.
 */
abstract class FixtureBasedTestCase extends FilesystemTestCase
{
    /**
     * Get the name of the tool being tested.
     * This should match the directory name in tests/Fixtures/.
     */
    abstract protected function getToolName(): string;

    /**
     * Execute the tool with the given fixture data.
     *
     * @param string $fixtureName The name of the fixture (without .php extension)
     * @param string $code The PHP code from the fixture file
     * @param array<string, mixed> $params Parameters parsed from fixture comments
     *
     * @return array<string, mixed>
     */
    abstract protected function executeTool(string $fixtureName, string $code, array $params): array;

    /**
     * Data provider that discovers and yields all fixtures for this tool.
     *
     * @return iterable<string, array{fixtureName: string, code: string, params: array<string, mixed>}>
     */
    public static function fixtureProvider(): iterable
    {
        $toolName = static::getToolNameStatic();
        $fixturesDir = __DIR__ . '/../Fixtures/' . $toolName;

        if (!is_dir($fixturesDir)) {
            return;
        }

        // Find all .php files in the fixtures directory
        foreach (new \DirectoryIterator($fixturesDir) as $file) {
            if ($file->isDot() || !$file->isFile()) {
                continue;
            }

            // Only process .php files
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $fixtureName = $file->getBasename('.php');
            $fixturePath = $file->getPathname();
            $fixtureContent = file_get_contents($fixturePath);

            if ($fixtureContent === false) {
                continue;
            }

            // Parse parameters from comments
            $params = self::parseFixtureParams($fixtureContent);

            yield $fixtureName => [
                'fixtureName' => $fixtureName,
                'code' => $fixtureContent,
                'params' => $params,
            ];
        }
    }

    /**
     * Get tool name statically (for use in data provider).
     */
    protected static function getToolNameStatic(): string
    {
        // Create temporary instance to get tool name
        $reflection = new \ReflectionClass(static::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        return $instance->getToolName();
    }

    /**
     * Parse parameters from fixture file comments.
     *
     * Looks for comment lines like:
     * // param_name: value
     *
     * @return array<string, mixed>
     */
    protected static function parseFixtureParams(string $content): array
    {
        $params = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // Match comment lines with parameters: // key: value
            if (preg_match('/^\/\/\s*(\w+):\s*(.+)$/', $line, $matches)) {
                $key = $matches[1];
                $value = trim($matches[2]);
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * Test a fixture.
     *
     * This is the main test method that will be run for each fixture via the data provider.
     *
     * @dataProvider fixtureProvider
     *
     * @param array<string, mixed> $params
     */
    public function testFixture(string $fixtureName, string $code, array $params): void
    {
        $result = $this->executeTool($fixtureName, $code, $params);

        // All fixtures are expected to succeed - error cases should be regular test methods
        $this->assertTrue($result['success'], 'Expected successful execution for fixture: ' . $fixtureName);
        $this->assertArrayHasKey('code', $result, 'Expected code in result');

        // Use snapshot testing to verify the output
        $this->assertValidPhpSnapshot($result['code']);
    }
}

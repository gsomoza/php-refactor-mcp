<?php

declare(strict_types=1);

namespace Somoza\PhpRefactorMcp\Tests\Support;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PhpParser\Error;
use PHPUnit\Framework\TestCase;
use Somoza\PhpRefactorMcp\Helpers\RefactoringHelpers;
use Spatie\Snapshots\MatchesSnapshots;

abstract class FilesystemTestCase extends TestCase
{
    use MatchesSnapshots;

    /** @phpstan-ignore-next-line property.uninitializedProperty */
    protected FilesystemOperator $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create in-memory filesystem for testing
        $adapter = new InMemoryFilesystemAdapter();
        $this->filesystem = new Filesystem($adapter);
    }

    /**
     * Create a virtual file in the in-memory filesystem.
     */
    protected function createFile(string $path, string $content): string
    {
        $this->filesystem->write($path, $content);
        return $path;
    }

    /**
     * Check if a file exists in the in-memory filesystem.
     */
    protected function virtualFileExists(string $path): bool
    {
        return $this->filesystem->fileExists($path);
    }

    /**
     * Read a file from the in-memory filesystem.
     */
    protected function readVirtualFile(string $path): string
    {
        return $this->filesystem->read($path);
    }

    /**
     * Assert that the refactored code matches a snapshot and is valid PHP.
     */
    protected function assertValidPhpSnapshot(string $code): void
    {
        // Assert the code is valid PHP by parsing it
        $this->assertValidPhp($code);

        // Match against snapshot
        $this->assertMatchesSnapshot($code);
    }

    /**
     * Assert that the given code is valid PHP (can be parsed without errors).
     * Uses PHP Parser library instead of external php -l command.
     */
    protected function assertValidPhp(string $code): void
    {
        try {
            RefactoringHelpers::parseCode($code);
        } catch (Error $e) {
            $this->fail("Code is not valid PHP: " . $e->getMessage() . "\n\nCode:\n" . $code);
        }
    }
}

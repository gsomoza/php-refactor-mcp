<?php

declare(strict_types=1);

namespace Somoza\PhpRefactorMcp\Tests\Support;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Somoza\PhpRefactorMcp\Services\FilesystemService;
use Spatie\Snapshots\MatchesSnapshots;

abstract class FilesystemTestCase extends TestCase
{
    use MatchesSnapshots;

    /** @phpstan-ignore-next-line property.uninitializedProperty */
    protected FilesystemService $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create in-memory filesystem for testing
        $adapter = new InMemoryFilesystemAdapter();
        $flysystem = new Filesystem($adapter);
        $this->filesystem = new FilesystemService($flysystem);
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
     */
    protected function assertValidPhp(string $code): void
    {
        // Write to a temporary file and use php -l to lint it
        $tempFile = tempnam(sys_get_temp_dir(), 'php_lint_');
        file_put_contents($tempFile, $code);

        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($tempFile) . " 2>&1", $output, $returnCode);

        unlink($tempFile);

        $this->assertEquals(
            0,
            $returnCode,
            "Code is not valid PHP:\n" . implode("\n", $output) . "\n\nCode:\n" . $code
        );
    }
}

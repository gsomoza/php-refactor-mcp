<?php

declare(strict_types=1);

namespace Somoza\PhpRefactorMcp\Tests\Support;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Somoza\PhpRefactorMcp\Services\FilesystemService;

abstract class FilesystemTestCase extends TestCase
{
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
}

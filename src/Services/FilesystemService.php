<?php

declare(strict_types=1);

namespace Somoza\PhpRefactorMcp\Services;

use League\Flysystem\FilesystemOperator;

/**
 * Service for filesystem operations using Flysystem.
 */
class FilesystemService
{
    public function __construct(
        private readonly FilesystemOperator $filesystem
    ) {}

    /**
     * Check if a file exists.
     */
    public function fileExists(string $path): bool
    {
        return $this->filesystem->fileExists($path);
    }

    /**
     * Read the contents of a file.
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function read(string $path): string
    {
        return $this->filesystem->read($path);
    }

    /**
     * Write contents to a file.
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function write(string $path, string $contents): void
    {
        $this->filesystem->write($path, $contents);
    }

    /**
     * Get the underlying filesystem operator.
     */
    public function getFilesystem(): FilesystemOperator
    {
        return $this->filesystem;
    }
}

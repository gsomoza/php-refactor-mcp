<?php

declare(strict_types=1);

namespace Somoza\PhpRefactorMcp\Helpers;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Factory for creating filesystem instances.
 */
class FilesystemFactory
{
    /**
     * Create a default local filesystem instance.
     */
    public static function createLocalFilesystem(string $root = '/'): FilesystemOperator
    {
        $adapter = new LocalFilesystemAdapter($root);
        return new Filesystem($adapter);
    }
}

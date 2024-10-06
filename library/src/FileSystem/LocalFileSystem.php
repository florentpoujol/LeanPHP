<?php

declare(strict_types=1);

namespace LeanPHP\FileSystem;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class LocalFileSystem implements FileSystemInterface
{
    public function __construct(
        private string $root,
    ) {
        $this->root = '/' . trim($this->root, '/') . '/';
    }

    public function write(string $path, string $content, array $config = []): void
    {
        file_put_contents($this->root . $path, $content);
    }

    public function createDirectory(string $path, array $config = []): void
    {
        $path = $this->root . $path;

        mkdir($path, recursive: true);

        if (! is_dir($this->root . $path)) {
            throw new Exception('Directory "%s" was not created');
        }
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        copy($source, $destination);
        unlink($source);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        copy($source, $destination);
    }

    public function delete(string $path): void
    {
        $path = $this->root . $path;

        if (is_dir($path)) {
            throw new Exception('Is a directory');
        }

        if (! file_exists($path)) {
            return;
        }

        unlink($path);
    }

    public function deleteDirectory(string $path): void
    {
        $path = $this->root . $path;

        if (! file_exists($path)) { // would return true even for directory
            return;
        }

        if (! is_dir($path)) {
            throw new Exception('Not a directory');
        }

        rmdir($path);
    }

    public function read(string $path): string
    {
        $path = $this->root . $path;

        if (! file_exists($path)) {
            throw new Exception('File don\'t exists');
        }

        if (is_dir($path)) {
            throw new Exception('Is a directory');
        }

        $content = file_get_contents($path);
        \assert(\is_string($content));

        return $content;
    }

    public function listContents(string $path, bool $recursive = true): array
    {
        if ($recursive) {
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root . $path));

            $contents = [];
            /** @var SplFileInfo $fileInfo */
            foreach ($rii as $fileInfo) {
                if ($fileInfo->isDir()) {
                    continue;
                }

                $contents[] = new FileInfo(
                    $fileInfo,
                    $fileInfo->getRealPath(),
                );
            }

            return $contents;
        }

        $files = scandir($this->root . $path);
        \assert(\is_array($files));

        $contents = [];
        foreach ($files as $path) { // @phpstan-ignore-line (Foreach overwrites $path with its value variable.)
            if (str_ends_with($path, '.')) {
                continue;
            }

            $contents[] = new FileInfo(
                new SplFileInfo($path),
                $path,
            );
        }

        return $contents;
    }

    public function lastModified(string $path): int
    {
        return filemtime($this->root . $path);
    }

    public function mimeType(string $path): string
    {
        // require fileinfo extension
        return mime_content_type($path);
    }

    public function fileSize(string $path): int
    {
        return filesize($this->root . $path);
    }

    public function visibility(string $path): string
    {
        return 'public';
    }
}

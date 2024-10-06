<?php declare(strict_types=1);

namespace LeanPHP\FileSystem;

interface FileSystemInterface
{
    /**
     * @param array<string, string|array<int|string>> $config
     */
    public function write(string $path, string $content, array $config = []): void;

    /**
     * @param array<string, string|array<int|string>> $config
     */
    public function createDirectory(string $path, array $config = []): void;

    /**
     * @param array<string, string|array<int|string>> $config
     */
    public function move(string $source, string $destination, array $config = []): void;

    /**
     * @param array<string, string|array<int|string>> $config
     */
    public function copy(string $source, string $destination, array $config = []): void;

    public function delete(string $path): void;

    public function deleteDirectory(string $path): void;

    // --------------------------------------------------

    public function read(string $path): string;

    /**
     * @return array<FileInfo>
     */
    public function listContents(string $path): array;

    public function lastModified(string $path): int;

    public function mimeType(string $path): string;

    public function fileSize(string $path): int;

    public function visibility(string $path): string;
}

<?php declare(strict_types=1);

namespace LeanPHP\FileSystem;

use Exception;

final class ArrayFileSystem implements FileSystemInterface
{
    /**
     * @var array<string, array<FileInfo>|FileInfo>
     */
    private array $directory = [];

    public function writeFileInfo(string $path, FileInfo $fileInfo): void
    {
        $segments = explode('/', $path);

        $lastSegment = array_pop($segments);
        $directory = &$this->directory;
        foreach ($segments as $segment) {
            $directory[$segment] ??= [];

            $directory = &$directory[$segment];
        }

        $directory[$lastSegment] = $fileInfo;
    }

    /**
     * @inheritDoc
     */
    public function write(string $path, string $content, array $config = []): void
    {
        $this->writeFileInfo($path, new FileInfo(
                $content,
                $path,
                ...($config['fileinfo'] ?? []), // @phpstan-ignore-line (Only iterables can be unpacked, array|string given in argument #3.)
            ),
        );
    }

    // public function write2(string $path, string $content, array $config = []): void
    // {
    //     $this->directory[$path] = new FileInfo(
    //         $content,
    //         $path,
    //         ...($config['fileinfo'] ?? [])
    //     );
    // }

    public function createDirectory(string $path, array $config = []): void
    {
        $segments = explode('/', $path);

        $directory = &$this->directory;
        foreach ($segments as $segment) {
            $directory[$segment] ??= [];

            $directory = &$directory[$segment];
        }
    }

    // public function createDirectory2(string $path, array $config = []): void
    // {
    //     $this->directory[$path] = [];
    // }

    public function move(string $source, string $destination, array $config = []): void
    {
        $this->write($destination, $this->read($source), $config);
        $this->delete($source);
    }

    /**
     * @inheritDoc
     */
    public function copy(string $source, string $destination, array $config = []): void
    {
        $fileInfo = $this->getFileInfo($source);

        $config['fileinfo'] ??= [];
        \assert(\is_array($config['fileinfo']));
        $config['fileinfo'] = array_merge($fileInfo->getMetadata(), $config['fileinfo']);

        $this->write($destination, $fileInfo->getContent(), $config);
    }

    public function delete(string $path): void
    {
        $segments = explode('/', $path);

        $lastSegment = array_pop($segments);

        $directory = &$this->directory;
        foreach ($segments as $segment) {
            if (! isset($directory[$segment])) {
                return;
            }

            $directory = &$directory[$segment];
        }

        if (! isset($directory[$lastSegment])) {
            return;
        }

        if (\is_array($directory[$lastSegment])) {
            throw new Exception('Is a directory');
        }

        unset($directory[$lastSegment]);
    }

    public function delete2(string $path): void
    {
        unset($this->directory[$path]);
    }

    public function deleteDirectory(string $path): void
    {
        $segments = explode('/', $path);

        $lastSegment = array_pop($segments);

        $directory = &$this->directory;
        foreach ($segments as $segment) {
            if (! isset($directory[$segment])) {
                return;
            }

            $directory = &$directory[$segment];
        }

        if (! isset($directory[$lastSegment])) {
            return;
        }

        if (! \is_array($directory[$lastSegment])) {
            throw new Exception('Not a directory');
        }

        unset($directory[$lastSegment]);
    }

    public function getFileInfo(string $path): FileInfo
    {
        $segments = explode('/', $path);

        $lastSegment = array_pop($segments);

        $directory = &$this->directory;
        foreach ($segments as $segment) {
            if (! isset($directory[$segment])) {
                throw new Exception('File not found');
            }

            $directory = &$directory[$segment];
        }

        if (! isset($directory[$lastSegment])) {
            throw new Exception('File not found');
        }

        if (\is_array($directory[$lastSegment])) {
            throw new Exception('Is a directory');
        }

        return $directory[$lastSegment];
    }

    public function read(string $path): string
    {
        return $this->getFileInfo($path)->getContent();
    }

    public function read2(string $path): string
    {
        $content = $this->directory[$path] ?? null;

        if ($content === null) {
            throw new Exception('File not found');
        }

        if (\is_array($content)) {
            throw new Exception('Is a directory');
        }

        return $content->getContent();
    }

    public function listContents(string $path): array
    {
        $segments = explode('/', $path);

        $directory = &$this->directory;
        $contents = [];

        $currentPath = '';
        foreach ($segments as $segment) {
            $currentPath .= "/$segment";
            if (! isset($directory[$segment])) {
                return $contents;
            }

            if (\is_array($directory[$segment])) {
                $contents[] = $this->listContents($currentPath);

                continue;
            }

            $contents = [$directory[$segment]];
        }

        return array_merge($contents);
    }

    /**
     * @return array<FileInfo>
     */
    public function listContents2(string $path): array
    {
        $contents = [];
        foreach ($this->directory as $key => $fileInfo) {
            if (str_starts_with($key, $path)) {
                $contents[] = $fileInfo;
            }
        }

        return $contents;
    }

    public function lastModified(string $path): int
    {
        return $this->getFileInfo($path)->lastModified;
    }

    public function mimeType(string $path): string
    {
        return $this->getFileInfo($path)->mimeType;
    }

    public function fileSize(string $path): int
    {
        return \strlen($this->getFileInfo($path)->getContent());
    }

    public function visibility(string $path): string
    {
        return $this->getFileInfo($path)->visibility;
    }

    public function sync(FileSystemInterface $otherFileSystem): void
    {
        $contents = $this->listContents('/');
        foreach ($contents as $fileInfo) {
            $otherFileSystem->write($fileInfo->path, $fileInfo->getContent());
        }
    }
}

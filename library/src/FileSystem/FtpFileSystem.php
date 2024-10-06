<?php

declare(strict_types=1);

namespace LeanPHP\FileSystem;

use FTP\Connection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * This requires the FTP PHP extension.
 */
final class FtpFileSystem implements FileSystemInterface
{
    private ?Connection $connection;

    public function __construct(
        private string $root,
        private readonly string $hostname,
        private readonly int $port = 21,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
    ) {
        $this->root = '/' . trim($this->root, '/') . '/';
    }

    public function connect(): self
    {
        if ($this->connection !== null) {
            return $this;
        }

        $connection = ftp_connect($this->hostname, $this->port);
        // ftp_ssl_connect()
        \assert($connection instanceof Connection);
        $this->connection = $connection;

        if ($this->username !== null && $this->password !== null) {
            ftp_login($this->connection, $this->username, $this->password);
        }

        return $this;
    }

    public function disconnect(): self
    {
        if ($this->connection !== null) {
            ftp_close($this->connection);
            $this->connection = null;
        }

        return $this;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function getConnection(): Connection
    {
        $this->connect();
        \assert($this->connection instanceof Connection);

        return $this->connection;
    }

    // --------------------------------------------------

    public function write(string $path, string $content, array $config = []): void
    {
        $tmpFileName = 'php://tmp/' . md5(uniqid('', true));
        $tmpResource = fopen($tmpFileName, 'w+');
        \assert(\is_resource($tmpResource));

        fwrite($tmpResource, $content);
        rewind($tmpResource);

        $this->connect();

        ftp_fput($this->connection, $this->root . $path, $tmpResource);

        fclose($tmpResource);
        unlink($tmpFileName);
    }

    public function createDirectory(string $path, array $config = []): void
    {
        $this->connect();

        ftp_mkdir($this->connection, $this->root . $path);
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        ftp_rename($this->connection, $this->root . $source, $this->root . $destination);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        $tmpFileName = 'php://tmp/' . md5(uniqid('', true));
        $tmpResource = fopen($tmpFileName, 'w+');
        \assert(\is_resource($tmpResource));

        $this->connect();

        ftp_fget($this->connection, $tmpResource, $this->root . $source);

        rewind($tmpResource);

        ftp_fput($this->connection, $this->root . $destination, $tmpResource);

        fclose($tmpResource);
        unlink($tmpFileName);
    }

    public function delete(string $path): void
    {
        $this->connect();

        ftp_delete($this->connection, $this->root . $path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->connect();

        ftp_rmdir($this->connection, $this->root . $path); // only delete empty directory
    }

    public function read(string $path): string
    {
        $tmpFileName = 'php://tmp/smol-ftp-read-' . md5(uniqid('', true));
        $tmpResource = fopen($tmpFileName, 'w+');
        \assert(\is_resource($tmpResource));

        $this->connect();

        ftp_fget($this->connection, $tmpResource, $this->root . $path);
        fclose($tmpResource);

        $content = file_get_contents($tmpFileName);
        \assert(\is_string($content));

        return $content;
    }

    // TODO
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
        $this->connect();

        return ftp_mdtm($this->connection, $this->root . $path);
    }

    public function mimeType(string $path): string
    {
        $tmpFileName = 'php://tmp/smol-ftp-read-' . md5(uniqid('', true));
        $tmpResource = fopen($tmpFileName, 'w+');
        \assert(\is_resource($tmpResource));

        $this->connect();

        ftp_fget($this->connection, $tmpResource, $this->root . $path);
        fclose($tmpResource);

        // require fileinfo extension
        $content = mime_content_type($tmpFileName);
        \assert(\is_string($content));

        return $content;
    }

    public function fileSize(string $path): int
    {
        $this->connect();

        return ftp_size($this->connection, $this->root . $path);
    }

    public function visibility(string $path): string
    {
        return 'public';
    }
}

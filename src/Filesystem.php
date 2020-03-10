<?php

namespace Yiisoft\Files;

use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use Yiisoft\Aliases\Aliases;

class Filesystem extends LeagueFilesystem implements FilesystemInterface
{
    private Aliases $aliases;

    public function __construct(FilesystemAdapter $adapter, array $aliases = [], array $config = [], PathNormalizer $pathNormalizer = null)
    {
        if ($aliases !== []) {
            $aliases = array_merge(['@root' => ''], $aliases);
            $aliases['@root'] = '';
        }
        $this->aliases = new Aliases($aliases);

        parent::__construct($adapter, $config, $pathNormalizer);
    }

    public function fileExists(string $location): bool
    {
        $location = $this->aliases->get($location);
        return parent::fileExists($location);
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        $location = $this->aliases->get($location);
        parent::write($location, $contents, $config);
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        $location = $this->aliases->get($location);
        parent::writeStream($location, $contents, $config);
    }

    public function read(string $location): string
    {
        $location = $this->aliases->get($location);
        return parent::read($location);
    }

    public function readStream(string $location)
    {
        $location = $this->aliases->get($location);
        return parent::readStream($location);
    }

    public function delete(string $location): void
    {
        $location = $this->aliases->get($location);
        parent::delete($location);
    }

    public function createDirectory(string $location, array $config = []): void
    {
        $location = $this->aliases->get($location);
        parent::createDirectory($location, $config);
    }

    public function deleteDirectory(string $location): void
    {
        $location = $this->aliases->get($location);
        parent::deleteDirectory($location);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        $source = $this->aliases->get($source);
        $destination = $this->aliases->get($destination);
        parent::copy($source, $destination, $config);
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        $source = $this->aliases->get($source);
        $destination = $this->aliases->get($destination);
        parent::move($source, $destination, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $path = $this->aliases->get($path);
        parent::setVisibility($path, $visibility);
    }

    public function visibility(string $path): string
    {
        $path = $this->aliases->get($path);
        return parent::visibility($path);
    }

    public function mimeType(string $path): string
    {
        $path = $this->aliases->get($path);
        return parent::mimeType($path);
    }

    public function lastModified(string $path): int
    {
        $path = $this->aliases->get($path);
        return parent::lastModified($path);
    }

    public function listContents(string $location, bool $deep = LeagueFilesystem::LIST_SHALLOW): \League\Flysystem\DirectoryListing
    {
        $location = $this->aliases->get($location);
        return parent::listContents($location, $deep);
    }

    public function fileSize(string $path): int
    {
        $path = $this->aliases->get($path);
        return parent::fileSize($path);
    }
}

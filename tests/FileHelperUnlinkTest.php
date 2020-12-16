<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests;

use Yiisoft\Files\FileHelper;

final class FileHelperUnlinkTest extends FileSystemTestCase
{
    public function testUnlinkSymlink(): void
    {
        $dirName = 'unlink';
        $basePath = $this->testFilePath . '/' . $dirName . '/';
        $symlinkedFilePath = $basePath . 'symlinks/symlinked-file';

        $this->createFileStructure([
            $dirName => [
                'file' => 'Symlinked file.',
                'symlinks' => [
                    'symlinked-file' => ['symlink', '../file'],
                ],
            ],
        ]);

        FileHelper::unlink($symlinkedFilePath);

        $this->assertFileDoesNotExist($symlinkedFilePath);
    }

    public function testUnlinkSymlinkToNonexistentsDirectory(): void
    {
        $dirName = 'unlink-symlink-to-nonexistents-directory';
        $this->createFileStructure([
            $dirName => [
                'dir' => [
                    'file.txt' => 'content'
                ],
                'symlink' => ['symlink', 'dir'],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName;

        unlink($basePath . '/dir/file.txt');
        rmdir($basePath . '/dir');
        $this->assertDirectoryDoesNotExist($basePath . '/dir');

        FileHelper::unlink($basePath . '/symlink');
        $this->assertFalse(is_link($basePath . '/symlink'));
    }

    public function testUnlinkSymlinkToNonexistentsFile(): void
    {
        $dirName = 'unlink-symlink-to-nonexistents-file';
        $this->createFileStructure([
            $dirName => [
                'file.txt' => 'content',
                'symlink.txt' => ['symlink', 'file.txt'],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName;

        unlink($basePath . '/file.txt');
        $this->assertFileDoesNotExist($basePath . '/file.txt');

        FileHelper::unlink($basePath . '/symlink.txt');
        $this->assertFalse(is_link($basePath . '/symlink.txt'));
    }

    /**
     * 777 gives "read only" flag under Windows
     *
     * @see https://github.com/yiisoft/files/issues/21
     */
    public function testUnlinkFile777(): void
    {
        $dirName = 'unlink';
        $basePath = $this->testFilePath . '/' . $dirName . '/';
        $filePath = $basePath . 'file.txt';

        $this->createFileStructure([
            $dirName => [
                'file.txt' => 'test',
            ],
        ]);
        chmod($filePath, 777);

        FileHelper::unlink($filePath);

        $this->assertFileDoesNotExist($filePath);
    }

    public function testDirectory(): void
    {
        $dirName = 'unlink';
        $basePath = $this->testFilePath . '/' . $dirName . '/';

        $symlinkedDirectoryPath = $basePath . 'symlinks/symlinked-directory';

        $this->createFileStructure([
            $dirName => [
                'directory' => [
                    'file_in_directory' => 'File in directory.',
                ],
                'symlinks' => [
                    'symlinked-directory' => ['symlink', '../directory'],
                ],
            ],
        ]);

        FileHelper::unlink($symlinkedDirectoryPath);

        $this->assertDirectoryDoesNotExist($symlinkedDirectoryPath);
    }

    public function testUnlinkNonexistentFile(): void
    {
        $this->expectWarning();
        FileHelper::unlink($this->testFilePath . '/not-exists-file.txt');
    }
}

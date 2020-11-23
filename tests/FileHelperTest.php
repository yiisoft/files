<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests;

use RuntimeException;
use Yiisoft\Files\FileHelper;

/**
 * File helper tests class.
 */
final class FileHelperTest extends FileSystemTestCase
{
    public function testCreateDirectory(): void
    {
        $basePath = $this->testFilePath;
        $directory = $basePath . '/test_dir_level_1/test_dir_level_2';
        $this->assertTrue(FileHelper::createDirectory($directory), 'FileHelper::createDirectory should return true if directory was created!');
        $this->assertFileExists($directory, 'Unable to create directory recursively!');
        $this->assertTrue(FileHelper::createDirectory($directory), 'FileHelper::createDirectory should return true for already existing directories!');
    }

    public function testCreateDirectoryPermissions(): void
    {
        if (!$this->isChmodReliable()) {
            $this->markTestSkipped('Skipping test since chmod is not reliable in this environment.');
        }

        $basePath = $this->testFilePath;
        $dirName = $basePath . '/test_dir_perms';

        $this->assertTrue(FileHelper::createDirectory($dirName, 0700));
        $this->assertFileMode(0700, $dirName);
    }

    public function testCreateDirectoryException(): void
    {
        $this->createFileStructure([
            'test_dir' => [
                'file.txt' => 'file content',
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^Failed to create directory/');
        FileHelper::createDirectory($this->testFilePath . '/test_dir/file.txt');
    }

    public function testRemoveDirectory(): void
    {
        $dirName = 'test_dir_for_remove';

        $this->createFileStructure([
            $dirName => [
                'file1.txt' => 'file 1 content',
                'file2.txt' => 'file 2 content',
                'test_sub_dir' => [
                    'sub_dir_file_1.txt' => 'sub dir file 1 content',
                    'sub_dir_file_2.txt' => 'sub dir file 2 content',
                ],
            ],
        ]);

        $basePath = $this->testFilePath;
        $dirName = $basePath . '/' . $dirName;

        FileHelper::removeDirectory($dirName);

        $this->assertFileDoesNotExist($dirName, 'Unable to remove directory!');

        // should be silent about non-existing directories
        FileHelper::removeDirectory($basePath . '/nonExisting');
    }

    public function testRemoveDirectorySymlinks1(): void
    {
        $dirName = 'remove-directory-symlinks-1';

        $this->createFileStructure([
            $dirName => [
                'file' => 'Symlinked file.',
                'directory' => [
                    'standard-file-1' => 'Standard file 1.',
                ],
                'symlinks' => [
                    'standard-file-2' => 'Standard file 2.',
                    'symlinked-file' => ['symlink', '../file'],
                    'symlinked-directory' => ['symlink', '../directory'],
                ],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName . '/';

        $this->assertFileExists($basePath . 'file');
        $this->assertDirectoryExists($basePath . 'directory');
        $this->assertFileExists($basePath . 'directory/standard-file-1');
        $this->assertDirectoryExists($basePath . 'symlinks');
        $this->assertFileExists($basePath . 'symlinks/standard-file-2');
        $this->assertFileExists($basePath . 'symlinks/symlinked-file');
        $this->assertDirectoryExists($basePath . 'symlinks/symlinked-directory');
        $this->assertFileExists($basePath . 'symlinks/symlinked-directory/standard-file-1');

        FileHelper::removeDirectory($basePath . 'symlinks');

        $this->assertFileExists($basePath . 'file');
        $this->assertDirectoryExists($basePath . 'directory');
        $this->assertFileExists($basePath . 'directory/standard-file-1'); // symlinked directory still have it's file
        $this->assertDirectoryDoesNotExist($basePath . 'symlinks');
        $this->assertFileDoesNotExist($basePath . 'symlinks/standard-file-2');
        $this->assertFileDoesNotExist($basePath . 'symlinks/symlinked-file');
        $this->assertDirectoryDoesNotExist($basePath . 'symlinks/symlinked-directory');
        $this->assertFileDoesNotExist($basePath . 'symlinks/symlinked-directory/standard-file-1');
    }

    public function testRemoveDirectorySymlinks2(): void
    {
        $dirName = 'remove-directory-symlinks-2';

        $this->createFileStructure([
            $dirName => [
                'file' => 'Symlinked file.',
                'directory' => [
                    'standard-file-1' => 'Standard file 1.',
                ],
                'symlinks' => [
                    'standard-file-2' => 'Standard file 2.',
                    'symlinked-file' => ['symlink', '../file'],
                    'symlinked-directory' => ['symlink', '../directory'],
                ],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName . '/';

        $this->assertFileExists($basePath . 'file');
        $this->assertDirectoryExists($basePath . 'directory');
        $this->assertFileExists($basePath . 'directory/standard-file-1');
        $this->assertDirectoryExists($basePath . 'symlinks');
        $this->assertFileExists($basePath . 'symlinks/standard-file-2');
        $this->assertFileExists($basePath . 'symlinks/symlinked-file');
        $this->assertDirectoryExists($basePath . 'symlinks/symlinked-directory');
        $this->assertFileExists($basePath . 'symlinks/symlinked-directory/standard-file-1');

        FileHelper::removeDirectory($basePath . 'symlinks', ['traverseSymlinks' => true]);

        $this->assertFileExists($basePath . 'file');
        $this->assertDirectoryExists($basePath . 'directory');
        $this->assertFileDoesNotExist($basePath . 'directory/standard-file-1'); // symlinked directory doesn't have it's file now
        $this->assertDirectoryDoesNotExist($basePath . 'symlinks');
        $this->assertFileDoesNotExist($basePath . 'symlinks/standard-file-2');
        $this->assertFileDoesNotExist($basePath . 'symlinks/symlinked-file');
        $this->assertDirectoryDoesNotExist($basePath . 'symlinks/symlinked-directory');
        $this->assertFileDoesNotExist($basePath . 'symlinks/symlinked-directory/standard-file-1');
    }

    public function testClearDirectory(): void
    {
        $dirName = 'test_dir_for_remove';

        $this->createFileStructure([
            $dirName => [
                'file1.txt' => 'file 1 content',
                'test_sub_dir' => [
                    'sub_dir_file_1.txt' => 'sub dir file 1 content',
                ],
            ],
        ]);

        $basePath = $this->testFilePath;
        $dirName = $basePath . '/' . $dirName;

        FileHelper::clearDirectory($dirName);

        $this->assertDirectoryExists($dirName);
        $this->assertFileDoesNotExist($dirName . '/file1.txt');
        $this->assertDirectoryDoesNotExist($dirName . '/test_sub_dir');

        $this->expectException(\InvalidArgumentException::class);
        FileHelper::clearDirectory($this->testFilePath . '/nonExisting');
    }

    public function testNormalizePath(): void
    {
        $this->assertEquals('/a/b', FileHelper::normalizePath('//a\\b/'));
        $this->assertEquals('/b/c', FileHelper::normalizePath('/a/../b/c'));
        $this->assertEquals('/c', FileHelper::normalizePath('/a\\b/../..///c'));
        $this->assertEquals('/c', FileHelper::normalizePath('/a/.\\b//../../c'));
        $this->assertEquals('c', FileHelper::normalizePath('/a/.\\b/../..//../c'));
        $this->assertEquals('../c', FileHelper::normalizePath('//a/.\\b//..//..//../../c'));

        // relative paths
        $this->assertEquals('.', FileHelper::normalizePath('.'));
        $this->assertEquals('.', FileHelper::normalizePath('./'));
        $this->assertEquals('a', FileHelper::normalizePath('.\\a'));
        $this->assertEquals('a/b', FileHelper::normalizePath('./a\\b'));
        $this->assertEquals('.', FileHelper::normalizePath('./a\\../'));
        $this->assertEquals('../../a', FileHelper::normalizePath('../..\\a'));
        $this->assertEquals('../../a', FileHelper::normalizePath('../..\\a/../a'));
        $this->assertEquals('../../b', FileHelper::normalizePath('../..\\a/../b'));
        $this->assertEquals('../a', FileHelper::normalizePath('./..\\a'));
        $this->assertEquals('../a', FileHelper::normalizePath('././..\\a'));
        $this->assertEquals('../a', FileHelper::normalizePath('./..\\a/../a'));
        $this->assertEquals('../b', FileHelper::normalizePath('./..\\a/../b'));

        // Windows file system may have paths for network shares that start with two backslashes. These two backslashes
        // should not be touched.
        // https://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        // https://github.com/yiisoft/yii2/issues/13034
        $this->assertEquals('\\\\server/share/path/file', FileHelper::normalizePath('\\\\server\share\path\file'));
        $this->assertEquals('\\\\server/share/path/file', FileHelper::normalizePath('\\\\server\share\path//file'));
    }

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

    public function testUnlinkDirectory(): void
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

    public function testIsEmptyDirectory(): void
    {
        $this->createFileStructure([
            'not-empty' => [
                'file.txt' => '42',
            ],
            'empty' => [],
        ]);

        $this->assertTrue(FileHelper::isEmptyDirectory($this->testFilePath . '/empty'));
        $this->assertFalse(FileHelper::isEmptyDirectory($this->testFilePath . '/not-empty'));
        $this->assertFalse(FileHelper::isEmptyDirectory($this->testFilePath . '/not-exists'));
    }

    public function testLastModifiedTime(): void
    {
        $dirName = 'assets';
        $basePath = $this->testFilePath . '/' . $dirName;

        $this->createFileStructure(
            [
                $dirName => [
                    'css' => [
                        'stub.css' => 'testMe',
                    ],
                    'js' => [
                        'stub.js' => 'testMe',
                    ],
                ],
            ]
        );

        $this->assertIsInt(FileHelper::lastModifiedTime($basePath));
    }
}

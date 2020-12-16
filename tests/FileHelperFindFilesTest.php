<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests;

use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

final class FileHelperFindFilesTest extends FileSystemTestCase
{
    public function testSimple()
    {
        $dirName = 'find-files-simple-test';
        $this->createFileStructure([
            $dirName => [
                'file1.txt' => 'content',
                'file2.txt' => 'content',
                'subdir' => [
                    'file3.txt' => 'content',
                    'file4.txt' => 'content',
                ],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName;
        $this->assertEqualsPaths(
            [
                $basePath . '/file1.txt',
                $basePath . '/file2.txt',
                $basePath . '/subdir/file3.txt',
                $basePath . '/subdir/file4.txt',
            ],
            FileHelper::findFiles($basePath)
        );
    }

    public function testFilter(): void
    {
        $dirName = 'find-files-filter-test';
        $this->createFileStructure([
            $dirName => [
                'file1.txt' => 'content',
                'file1-local.txt' => 'content',
                'subdir' => [
                    'file2.txt' => 'content',
                    'file2-local.txt' => 'content',
                ],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName;
        $this->assertEqualsPaths(
            [
                $basePath . '/file1.txt',
                $basePath . '/subdir/file2.txt',
            ],
            FileHelper::findFiles($basePath, [
                'filter' => (new PathMatcher())->except('-local.txt'),
            ])
        );
    }

    public function testNotRecursive(): void
    {
        $dirName = 'find-files-not-recursive-test';
        $this->createFileStructure([
            $dirName => [
                'file1.txt' => 'content',
                'file2.txt' => 'content',
                'subdir' => [
                    'file3.txt' => 'content',
                    'file4.txt' => 'content',
                ],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName;
        $this->assertEqualsPaths(
            [
                $basePath . '/file1.txt',
                $basePath . '/file2.txt',
            ],
            FileHelper::findFiles($basePath, [
                'recursive' => false,
            ])
        );
    }

    public function testWithSymLink()
    {
        $dirName = 'find-files-with-symlink-test';
        $this->createFileStructure([
            $dirName => [
                'file1.txt' => 'content',
                'dir' => [
                    'file2.txt' => 'content',
                ],
                'symdir' => ['symlink', 'dir'],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName;
        $this->assertEqualsPaths(
            [
                $basePath . '/file1.txt',
                $basePath . '/dir/file2.txt',
                $basePath . '/symdir/file2.txt',
            ],
            FileHelper::findFiles($basePath)
        );
    }
}

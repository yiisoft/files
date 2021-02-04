<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests;

use InvalidArgumentException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

final class FileHelperFindDirectoriesTest extends FileSystemTestCase
{
    public function testSimple(): void
    {
        $dirName = 'find-directory-simple-test';
        $this->createFileStructure([
            $dirName => [
                'dir1' => [
                    'file1.txt' => 'content',
                    'file2.txt' => 'content',
                    'subdir' => [
                        'file3.txt' => 'content',
                    ],
                ],
                'dir2' => [
                    'file4.txt' => 'content',
                ],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName;
        $this->assertEqualsPaths(
            [
                $basePath . '/dir1',
                $basePath . '/dir1/subdir',
                $basePath . '/dir2',
            ],
            FileHelper::findDirectories($basePath)
        );
    }

    public function testNotRecursive(): void
    {
        $dirName = 'find-directory-recursive-test';
        $this->createFileStructure([
            $dirName => [
                'dir1' => [
                    'file1.txt' => 'content',
                    'file2.txt' => 'content',
                    'subdir' => [
                        'file3.txt' => 'content',
                    ],
                ],
                'dir2' => [
                    'file4.txt' => 'content',
                ],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName;
        $this->assertEqualsPaths(
            [
                $basePath . '/dir1',
                $basePath . '/dir2',
            ],
            FileHelper::findDirectories($basePath, ['recursive' => false])
        );
    }

    public function testFilter(): void
    {
        $dirName = 'find-directory-filter-test';
        $this->createFileStructure([
            $dirName => [
                'dir1' => [
                    'file1.txt' => 'content',
                    'file2.txt' => 'content',
                    'subdir' => [
                        'file3.txt' => 'content',
                    ],
                    'subdir-local' => [
                        'file4.txt' => 'content',
                    ],
                ],
                'dir2' => [
                    'file5.txt' => 'content',
                ],
                'dir2-local' => [
                    'file6.txt' => 'content',
                ],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName;
        $this->assertEqualsPaths(
            [
                $basePath . '/dir1',
                $basePath . '/dir1/subdir',
                $basePath . '/dir2',
            ],
            FileHelper::findDirectories($basePath, [
                'filter' => (new PathMatcher())->except('-local/'),
            ])
        );
    }

    public function testInvalidFilter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter should be an instance of PathMatcherInterface');
        FileHelper::findDirectories('', [
            'filter' => 'wrong_type',
        ]);
    }

    public function testDeepFilter(): void
    {
        $dirName = 'find-directory-deep-filter-test';
        $this->createFileStructure([
            $dirName => [
                'dir1' => [
                    'file1.txt' => 'content',
                    'file2.txt' => 'content',
                    'subdir' => [
                        'file3.txt' => 'content',
                        'subdir-local' => [
                            'file4.txt' => 'content',
                        ],
                    ],
                ],
                'dir2' => [
                    'file5.txt' => 'content',
                ],
                'dir2-local' => [
                    'file6.txt' => 'content',
                ],
            ],
        ]);

        $basePath = $this->testFilePath . '/' . $dirName;
        $this->assertEqualsPaths(
            [
                $basePath . '/dir1/subdir/subdir-local',
                $basePath . '/dir2-local',
            ],
            FileHelper::findDirectories($basePath, [
                'filter' => (new PathMatcher())->only('-local/'),
            ])
        );
    }

    public function testIncorrectDirectory(): void
    {
        $dirName = 'find-directory-incorrect-directory-test';
        $this->createFileStructure([
            $dirName => [
                'file1.txt' => 'content',
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        FileHelper::findDirectories($this->testFilePath . '/' . $dirName . '/file1.txt');
    }
}

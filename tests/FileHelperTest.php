<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Files\FileHelper;

/**
 * File helper tests class.
 */
final class FileHelperTest extends TestCase
{
    /**
     * @var string test files path.
     */
    private string $testFilePath = '';

    public function setUp(): void
    {
        $this->testFilePath = FileHelper::normalizePath(realpath(sys_get_temp_dir()) . '/' . get_class($this));

        FileHelper::createDirectory($this->testFilePath, 0777);

        if (!file_exists($this->testFilePath)) {
            $this->markTestIncomplete('Unit tests runtime directory should have writable permissions!');
        }
    }

    public function tearDown(): void
    {
        FileHelper::removeDirectory($this->testFilePath);
    }

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

    /**
     * Copy directory.
     *
     * @depends testCreateDirectory
     *
     * @return void
     */
    public function testCopyDirectory(): void
    {
        $source = 'test_src_dir';
        $files = [
            'file1.txt' => 'file 1 content',
            'file2.txt' => 'file 2 content',
        ];

        $this->createFileStructure([
            $source => $files,
        ]);

        $basePath = $this->testFilePath;
        $source = $basePath . '/' . $source;
        $destination = $basePath . '/test_dst_dir';

        FileHelper::copyDirectory($source, $destination);

        $this->assertFileExists($destination, 'Destination directory does not exist!');

        foreach ($files as $name => $content) {
            $fileName = $destination . '/' . $name;
            $this->assertFileExists($fileName);
            $this->assertStringEqualsFile($fileName, $content, 'Incorrect file content!');
        }
    }

    public function testCopyDirectoryRecursive(): void
    {
        $source = 'test_src_dir_rec';
        $structure = [
            'directory1' => [
                'file1.txt' => 'file 1 content',
                'file2.txt' => 'file 2 content',
            ],
            'directory2' => [
                'file3.txt' => 'file 3 content',
                'file4.txt' => 'file 4 content',
            ],
            'file5.txt' => 'file 5 content',
        ];

        $this->createFileStructure([
            $source => $structure,
        ]);

        $basePath = $this->testFilePath;
        $source = $basePath . '/' . $source;
        $destination = $basePath . '/test_dst_dir';

        FileHelper::copyDirectory($source, $destination);

        $this->assertFileExists($destination, 'Destination directory does not exist!');
        $this->checkExist($structure, $destination);
    }

    public function testCopyDirectoryNotRecursive(): void
    {
        $source = 'test_src_dir_not_rec';
        $structure = [
            'directory1' => [
                'file1.txt' => 'file 1 content',
                'file2.txt' => 'file 2 content',
            ],
            'directory2' => [
                'file3.txt' => 'file 3 content',
                'file4.txt' => 'file 4 content',
            ],
            'file5.txt' => 'file 5 content',
        ];

        $this->createFileStructure([
            $source => $structure,
        ]);

        $basePath = $this->testFilePath;
        $source = $basePath . '/' . $source;
        $destination = $basePath . '/' . 'test_dst_dir';

        FileHelper::copyDirectory($source, $destination, ['recursive' => false]);

        $this->assertFileExists($destination, 'Destination directory does not exist!');

        foreach ($structure as $name => $content) {
            $fileName = $destination . '/' . $name;
            if (is_array($content)) {
                $this->assertFileDoesNotExist($fileName);
            } else {
                $this->assertFileExists($fileName);
                $this->assertStringEqualsFile($fileName, $content, 'Incorrect file content!');
            }
        }
    }

    public function testCopyDirectoryPermissions(): void
    {
        if (!$this->isChmodReliable()) {
            $this->markTestSkipped('Skipping test since chmod is not reliable in this environment.');
        }

        $isWindows = DIRECTORY_SEPARATOR === '\\';

        if ($isWindows) {
            $this->markTestSkipped('Skipping tests on Windows because fileperms() always return 0777.');
        }

        $source = 'test_src_dir';
        $subDirectory = 'test_sub_dir';
        $fileName = 'test_file.txt';

        $this->createFileStructure([
            $source => [
                $subDirectory => [],
                $fileName => 'test file content',
            ],
        ]);

        $basePath = $this->testFilePath;
        $source = $basePath . '/' . $source;
        $destination = $basePath . '/test_dst_dir';
        $directoryMode = 0755;
        $fileMode = 0755;
        $options = [
            'dirMode' => $directoryMode,
            'fileMode' => $fileMode,
        ];

        FileHelper::copyDirectory($source, $destination, $options);

        $this->assertFileMode($directoryMode, $destination, 'Destination directory has wrong mode!');
        $this->assertFileMode($directoryMode, $destination . '/' . $subDirectory, 'Copied sub directory has wrong mode!');
        $this->assertFileMode($fileMode, $destination . '/' . $fileName, 'Copied file has wrong mode!');
    }

    /**
     * Copy directory to it self.
     *
     * @see https://github.com/yiisoft/yii2/issues/10710
     *
     * @return void
     */
    public function testCopyDirectoryToItself(): void
    {
        $directoryName = 'test_dir';

        $this->createFileStructure([
            $directoryName => [],
        ]);
        $this->expectException(\InvalidArgumentException::class);

        $directoryName = $this->testFilePath . '/test_dir';

        FileHelper::copyDirectory($directoryName, $directoryName);
    }

    /**
     * Copy directory to sudirectory of it self.
     *
     * @see https://github.com/yiisoft/yii2/issues/10710
     *
     * @return void
     */
    public function testCopyDirToSubdirOfItself(): void
    {
        $this->createFileStructure([
            'data' => [],
            'backup' => ['data' => []],
        ]);
        $this->expectException(\InvalidArgumentException::class);

        FileHelper::copyDirectory(
            $this->testFilePath . '/backup',
            $this->testFilePath . '/backup/data'
        );
    }

    /**
     * Copy directory to another with same name.
     *
     * @see https://github.com/yiisoft/yii2/issues/10710
     *
     * @return void
     */
    public function testCopyDirToAnotherWithSameName(): void
    {
        $this->createFileStructure([
            'data' => [],
            'backup' => ['data' => []],
        ]);

        FileHelper::copyDirectory(
            $this->testFilePath . '/data',
            $this->testFilePath . '/backup/data'
        );

        $this->assertFileExists($this->testFilePath . '/backup/data');
    }

    /**
     * Copy directory with same name.
     *
     * @see https://github.com/yiisoft/yii2/issues/10710
     *
     * @return void
     */
    public function testCopyDirWithSameName(): void
    {
        $this->createFileStructure([
            'data' => [],
            'data-backup' => [],
        ]);

        FileHelper::copyDirectory(
            $this->testFilePath . '/data',
            $this->testFilePath . '/data-backup'
        );

        $this->assertTrue(true, 'no error');
    }

    public function testsCopyDirectoryFilterPath(): void
    {
        $source = 'boostrap4';

        $structure = [
            'css' => [
                'bootstrap.css' => 'file 1 content',
                'bootstrap.css.map' => 'file 2 content',
                'bootstrap.min.css' => 'file 3 content',
                'bootstrap.min.css.map' => 'file 4 content'
            ],
            'js' => [
                'bootstrap.js' => 'file 5 content',
                'bootstrap.bundle.js' => 'file 6 content',
                'bootstrap.bundle.js.map' => 'file 7 content',
                'bootstrap.min.js' => 'file 8 content'
            ]
        ];

        $this->createFileStructure([
            $source => $structure,
        ]);

        $basePath = $this->testFilePath;
        $source = $basePath . '/' . $source;
        $destination = $basePath . '/assets';

        // without filter options return all directory.
        $options = [];

        FileHelper::copyDirectory($source, $destination, $options);

        $this->assertFileExists($destination, 'Destination directory does not exist!');
        $this->checkExist($structure, $destination);
    }

    public function testsCopyDirectoryFilterPathOnly(): void
    {
        $source = 'boostrap4';

        $structure = [
            'css' => [
                'bootstrap.css' => 'file 1 content',
                'bootstrap.css.map' => 'file 2 content',
                'bootstrap.min.css' => 'file 3 content',
                'bootstrap.min.css.map' => 'file 4 content'
            ],
            'js' => [
                'bootstrap.js' => 'file 5 content',
                'bootstrap.bundle.js' => 'file 6 content',
                'bootstrap.bundle.js.map' => 'file 7 content',
                'bootstrap.min.js' => 'file 8 content'
            ]
        ];

        $exist = [
            'css' => [
                'bootstrap.css' => 'file 1 content',
                'bootstrap.min.css' => 'file 3 content',
            ]
        ];

        $noexist = [
            'css' => [
                'bootstrap.css.map' => 'file 2 content',
                'bootstrap.min.css.map' => 'file 4 content'
            ],
            'js' => [
                'bootstrap.js' => 'file 5 content',
                'bootstrap.bundle.js' => 'file 6 content',
                'bootstrap.bundle.js.map' => 'file 7 content',
                'bootstrap.min.js' => 'file 8 content'
            ]
        ];

        $this->createFileStructure([
            $source => $structure,
        ]);

        $basePath = $this->testFilePath;
        $source = $basePath . '/' . $source;
        $destination = $basePath . '/assets';

        // without filter options return all directory.
        $options = [
            // options default false AssetManager
            'copyEmptyDirectories' => false,
            'only' => [
                'css/*.css',
            ]
        ];

        FileHelper::copyDirectory($source, $destination, $options);

        $this->assertFileExists($destination, 'Destination directory does not exist!');
        $this->checkExist($exist, $destination);
        $this->checkNoexist($noexist, $destination);
    }

    public function testsCopyDirectoryFilterPathExcept(): void
    {
        $source = 'boostrap4';

        $structure = [
            'css' => [
                'bootstrap.css' => 'file 1 content',
                'bootstrap.css.map' => 'file 2 content',
                'bootstrap.min.css' => 'file 3 content',
                'bootstrap.min.css.map' => 'file 4 content'
            ],
            'js' => [
                'bootstrap.js' => 'file 5 content',
                'bootstrap.bundle.js' => 'file 6 content',
                'bootstrap.bundle.js.map' => 'file 7 content',
                'bootstrap.min.js' => 'file 8 content'
            ]
        ];

        $exist = [
            'css' => [
                'bootstrap.css' => 'file 1 content',
            ]
        ];

        $noexist = [
            'css' => [
                'bootstrap.css.map' => 'file 2 content',
                'bootstrap.min.css' => 'file 3 content',
                'bootstrap.min.css.map' => 'file 4 content'
            ],
            'js' => [
                'bootstrap.js' => 'file 5 content',
                'bootstrap.bundle.js' => 'file 6 content',
                'bootstrap.bundle.js.map' => 'file 7 content',
                'bootstrap.min.js' => 'file 8 content'
            ]
        ];

        $this->createFileStructure([
            $source => $structure,
        ]);

        $basePath = $this->testFilePath;
        $source = $basePath . '/' . $source;
        $destination = $basePath . '/assets';

        // without filter options return all directory.
        $options = [
            // options default false AssetManager
            'copyEmptyDirectories' => false,
            'only' => [
                'css/*.css',
            ],
            'except' => [
                'css/bootstrap.min.css'
            ]
        ];

        FileHelper::copyDirectory($source, $destination, $options);

        $this->assertFileExists($destination, 'Destination directory does not exist!');
        $this->checkExist($exist, $destination);
        $this->checkNoexist($noexist, $destination);
    }

    public function testCopyNotExistsDirectory(): void
    {
        $dir = $this->testFilePath . '/not_exists_dir';
        $this->expectExceptionMessage('Unable to open directory: ' . $dir);
        FileHelper::copyDirectory($dir, $this->testFilePath . '/copy');
    }

    public function dataFilterPath(): array
    {
        return [
            [
                [],
                true,
            ],
            [
                ['filter' => fn ($path) => true],
                true,
            ],
            [
                ['filter' => fn ($path) => false],
                false,
            ],
            [
                ['filter' => fn ($path) => null],
                true,
            ],
            [
                [
                    'filter' => fn ($path) => null,
                    'only' => '*.jpg',
                ],
                false,
            ],
            [
                ['only' => ['*.png']],
                true,
            ],
            [
                ['except' => ['*.png']],
                false,
            ],
            [
                ['only' => '*.jpg'],
                false,
            ],
            [
                ['except' => '*.jpg'],
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataFilterPath
     *
     * @param array $options
     * @param bool $expected
     */
    public function testFilterPath(array $options, bool $expected): void
    {
        $this->assertSame($expected, FileHelper::filterPath('/hello/world/i/here/face.png', $options));
    }

    public function dataFilterPathInvalidOptions(): array
    {
        return [
            [
                [
                    'only' => [[]],
                ]
            ],
            [
                [
                    'filter' => 42,
                ]
            ],

        ];
    }

    /**
     * @dataProvider dataFilterPathInvalidOptions
     *
     * @param array $options
     */
    public function testFilterPathInvalidOptions(array $options): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileHelper::filterPath('/42.png', $options);
    }

    /**
     * Check if exist filename.
     *
     * @param array $exist
     * @param string $dstDirName
     *
     * @return void
     */
    private function checkExist(array $exist, string $dstDirName): void
    {
        foreach ($exist as $name => $content) {
            if (is_array($content)) {
                $this->checkExist($content, $dstDirName . '/' . $name);
            } else {
                $fileName = $dstDirName . '/' . $name;
                $this->assertFileExists($fileName);
                $this->assertStringEqualsFile($fileName, $content, 'Incorrect file content!');
            }
        }
    }

    /**
     * Check if no exist filename.
     *
     * @param array $noexist
     * @param string $dstDirName
     *
     * @return void
     */
    private function checkNoexist(array $noexist, string $dstDirName): void
    {
        foreach ($noexist as $name => $content) {
            if (is_array($content)) {
                $this->checkNoexist($content, $dstDirName . '/' . $name);
            } else {
                $fileName = $dstDirName . '/' . $name;
                $this->assertFileDoesNotExist($fileName);
            }
        }
    }

    /**
     * Asserts that file has specific permission mode.
     *
     * @param int $expectedMode expected file permission mode.
     * @param string $fileName file name.
     * @param string $message error message
     *
     * @return void
     */
    private function assertFileMode(int $expectedMode, string $fileName, string $message = ''): void
    {
        $expectedMode = sprintf('%04o', $expectedMode);
        $this->assertEquals($expectedMode, $this->getMode($fileName), $message);
    }

    /**
     * Creates test files structure.
     *
     * @param array $items file system objects to be created in format: objectName => objectContent
     *                         Arrays specifies directories, other values - files.
     * @param string|null $basePath structure base file path.
     *
     * @return void
     */
    private function createFileStructure(array $items, ?string $basePath = null): void
    {
        $basePath = $basePath ?? $this->testFilePath;

        if (empty($basePath)) {
            $basePath = $this->testFilePath;
        }
        foreach ($items as $name => $content) {
            $itemName = $basePath . DIRECTORY_SEPARATOR . $name;
            if (is_array($content)) {
                if (isset($content[0], $content[1]) && $content[0] === 'symlink') {
                    symlink($basePath . '/' . $content[1], $itemName);
                } else {
                    mkdir($itemName, 0777, true);
                    $this->createFileStructure($content, $itemName);
                }
            } else {
                file_put_contents($itemName, $content);
            }
        }
    }

    /**
     * Get file permission mode.
     *
     * @param string $file file name.
     *
     * @return string permission mode.
     */
    private function getMode(string $file): string
    {
        return substr(sprintf('%04o', fileperms($file)), -4);
    }

    /**
     * Check if chmod works as expected.
     *
     * On remote file systems and vagrant mounts chmod returns true but file permissions are not set properly.
     */
    private function isChmodReliable(): bool
    {
        $directory = $this->testFilePath . '/test_chmod';
        mkdir($directory);
        chmod($directory, 0700);
        $mode = $this->getMode($directory);
        rmdir($directory);

        return $mode === '0700';
    }
}

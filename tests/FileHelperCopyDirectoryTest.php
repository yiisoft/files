<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests;

use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

final class FileHelperCopyDirectoryTest extends FileSystemTestCase
{
    public function testBase(): void
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

        $this->assertFileExists($destination, 'Destination directory does not exist.');

        foreach ($files as $name => $content) {
            $fileName = $destination . '/' . $name;
            $this->assertFileExists($fileName);
            $this->assertStringEqualsFile($fileName, $content, 'Incorrect file content.');
        }
    }

    public function testRecursive(): void
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

        $this->assertFileExists($destination, 'Destination directory does not exist.');
        $this->checkExist($structure, $destination);
    }

    public function testNotRecursive(): void
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

        $this->assertFileExists($destination, 'Destination directory does not exist.');

        foreach ($structure as $name => $content) {
            $fileName = $destination . '/' . $name;
            if (is_array($content)) {
                $this->assertFileDoesNotExist($fileName);
            } else {
                $this->assertFileExists($fileName);
                $this->assertStringEqualsFile($fileName, $content, 'Incorrect file content.');
            }
        }
    }

    public function testPermissions(): void
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

        $this->assertFileMode($directoryMode, $destination, 'Destination directory has wrong mode.');
        $this->assertFileMode($directoryMode, $destination . '/' . $subDirectory, 'Copied sub directory has wrong mode.');
        $this->assertFileMode($fileMode, $destination . '/' . $fileName, 'Copied file has wrong mode.');
    }

    /**
     * Copy directory to itself.
     *
     * @see https://github.com/yiisoft/yii2/issues/10710
     */
    public function testCopyToItself(): void
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
     * Copy directory to subdirectory of itself.
     *
     * @see https://github.com/yiisoft/yii2/issues/10710
     */
    public function testCopyToSubdirOfItself(): void
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
     */
    public function testCopyToAnotherWithSameName(): void
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
     */
    public function testCopyWithSameName(): void
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

    public function testCopyNotExistsDirectory(): void
    {
        $dir = $this->testFilePath . '/not_exists_dir';
        $this->expectExceptionMessage('Unable to open directory: ' . $dir);
        FileHelper::copyDirectory($dir, $this->testFilePath . '/copy');
    }

    public function testFilter1(): void
    {
        $source = 'boostrap4';

        $structure = [
            'css' => [
                'bootstrap.css' => 'file 1 content',
                'bootstrap.css.map' => 'file 2 content',
                'bootstrap.min.css' => 'file 3 content',
                'bootstrap.min.css.map' => 'file 4 content',
                'readme.txt' => 'readme',
            ],
            'js' => [
                'bootstrap.js' => 'file 5 content',
                'bootstrap.bundle.js' => 'file 6 content',
                'bootstrap.bundle.js.map' => 'file 7 content',
                'bootstrap.min.js' => 'file 8 content',
            ],
        ];

        $exist = [
            'css' => [
                'bootstrap.css' => 'file 1 content',
                'bootstrap.min.css' => 'file 3 content',
            ],
        ];

        $noexist = [
            'css' => [
                'bootstrap.css.map' => 'file 2 content',
                'bootstrap.min.css.map' => 'file 4 content',
                'readme.txt' => 'readme',
            ],
            'js' => [
                'bootstrap.js' => 'file 5 content',
                'bootstrap.bundle.js' => 'file 6 content',
                'bootstrap.bundle.js.map' => 'file 7 content',
                'bootstrap.min.js' => 'file 8 content',
            ],
        ];

        $this->createFileStructure([
            $source => $structure,
        ]);

        $basePath = $this->testFilePath;
        $source = $basePath . '/' . $source;
        $destination = $basePath . '/assets';

        FileHelper::copyDirectory($source, $destination, [
            'filter' => (new PathMatcher())
                ->only('/css/')
                ->except('*.map', '*.txt'),
        ]);

        $this->assertFileExists($destination, 'Destination directory does not exist!');
        $this->checkExist($exist, $destination);
        $this->checkNoexist($noexist, $destination);
    }

    public function testFilter2(): void
    {
        $source = 'boostrap4';

        $structure = [
            'css' => [
                'bootstrap.css' => 'file 1 content',
                'bootstrap.css.map' => 'file 2 content',
                'bootstrap.min.css' => 'file 3 content',
                'bootstrap.min.css.map' => 'file 4 content',
            ],
            'js' => [
                'bootstrap.js' => 'file 5 content',
                'bootstrap.bundle.js' => 'file 6 content',
                'bootstrap.bundle.js.map' => 'file 7 content',
                'bootstrap.min.js' => 'file 8 content',
            ],
            'readme' => [
                'how-to.txt' => 'file 9 content',
            ],
        ];

        $exist = [
            'css' => [
                'bootstrap.css' => 'file 1 content',
                'bootstrap.min.css' => 'file 3 content',
            ],
            'readme' => [
                'how-to.txt' => 'file 9 content',
            ],
        ];

        $noexist = [
            'css' => [
                'bootstrap.css.map' => 'file 2 content',
                'bootstrap.min.css.map' => 'file 4 content',
            ],
            'js' => [
                'bootstrap.js' => 'file 5 content',
                'bootstrap.bundle.js' => 'file 6 content',
                'bootstrap.bundle.js.map' => 'file 7 content',
                'bootstrap.min.js' => 'file 8 content',
            ],
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
            'filter' => (new PathMatcher())->only('css/*.css', 'readme/', '*.txt'),
        ];

        FileHelper::copyDirectory($source, $destination, $options);

        $this->assertFileExists($destination, 'Destination directory does not exist!');
        $this->checkExist($exist, $destination);
        $this->checkNoexist($noexist, $destination);
    }

    public function testFilter3(): void
    {
        $source = 'boostrap4';

        $structure = [
            'css' => [
                'bootstrap.css' => 'file 1 content',
                'bootstrap.css.map' => 'file 2 content',
                'bootstrap.min.css' => 'file 3 content',
                'bootstrap.min.css.map' => 'file 4 content',
            ],
            'js' => [
                'bootstrap.js' => 'file 5 content',
                'bootstrap.bundle.js' => 'file 6 content',
                'bootstrap.bundle.js.map' => 'file 7 content',
                'bootstrap.min.js' => 'file 8 content',
            ],
            'readme' => [
                'how-to.txt' => 'file 9 content',
            ],
        ];

        $exist = [
            'css' => [
                'bootstrap.css' => 'file 1 content',
            ],
        ];

        $noexist = [
            'css' => [
                'bootstrap.css.map' => 'file 2 content',
                'bootstrap.min.css' => 'file 3 content',
                'bootstrap.min.css.map' => 'file 4 content',
            ],
            'js' => [
                'bootstrap.js' => 'file 5 content',
                'bootstrap.bundle.js' => 'file 6 content',
                'bootstrap.bundle.js.map' => 'file 7 content',
                'bootstrap.min.js' => 'file 8 content',
            ],
            'readme' => [
                'how-to.txt' => 'file 9 content',
            ],
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
            'filter' => (new PathMatcher())
                ->only('css/*.css', '*.txt')
                ->except('css/bootstrap.min.css', 'readme/'),
        ];

        FileHelper::copyDirectory($source, $destination, $options);

        $this->assertFileExists($destination, 'Destination directory does not exist!');
        $this->checkExist($exist, $destination);
        $this->checkNoexist($noexist, $destination);
    }
}

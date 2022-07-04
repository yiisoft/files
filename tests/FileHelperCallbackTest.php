<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests;

use Yiisoft\Files\FileHelper;
use PHPUnit\Framework\TestCase;
use TypeError;

final class FileHelperCallbackTest extends FileSystemTestCase
{
    public function testSimpleBeforeCopy(): void
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
        $callbackCalled = false;

        $options = [
            'beforeCopy' => function (string $source, string $destination) use ($files, $basePath, &$callbackCalled) {
                $callbackCalled = true;
                $this->assertFileExists($source);

                if (is_file($source)) {
                    $basename = basename($source);
                    $this->assertTrue(isset($files[$basename]));
                    $this->assertStringEqualsFile($source, $files[$basename], 'Incorrect file content.');
                } elseif (is_dir($source)) {
                    $this->assertSame($basePath . '/test_src_dir', $source);
                }
            },
        ];

        FileHelper::copyDirectory($source, $destination, $options);

        $this->assertTrue($callbackCalled);
    }

    public function testBeforeCopyFalse(): void
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

        $options = [
            'beforeCopy' => static fn () => false,
        ];

        FileHelper::copyDirectory($source, $destination, $options);

        $this->assertFileDoesNotExist($destination, 'Destination directory does exist.');

        foreach ($files as $name => $content) {
            $fileName = $destination . '/' . $name;
            $this->assertFileDoesNotExist($fileName);
        }
    }

    public function testBeforeCopyExclude(): void
    {
        $source = 'test_src_dir';
        $files = [
            'file1.txt' => 'file 1 content',
            'file2.txt' => 'file 2 content',
            'config.ini' => 'some configuration',
        ];

        $this->createFileStructure([
            $source => $files,
        ]);

        $basePath = $this->testFilePath;
        $source = $basePath . '/' . $source;
        $destination = $basePath . '/test_dst_dir';

        $options = [
            'beforeCopy' => function (string $source) {
                if (is_file($source)) {
                    return pathinfo($source, PATHINFO_EXTENSION) === 'ini';
                }
            },
        ];

        FileHelper::copyDirectory($source, $destination, $options);

        $this->assertFileExists($destination, 'Destination directory does not exist.');

        foreach ($files as $name => $content) {
            $fileName = $destination . '/' . $name;
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);

            if ($extension === 'ini') {
                $this->assertFileExists($fileName);
            } else {
                $this->assertFileDoesNotExist($fileName);
            }
        }
    }

    public function testAfterCopy(): void
    {
        $compressor = new class () {
            public function compress(string $file): void
            {
                $mode = 'wb9';
                $destination = $file . '.gz';

                if (!is_file($file)) {
                    return;
                }

                if ($fp_out = gzopen($destination, $mode)) {
                    if ($fp_in = fopen($file, 'rb')) {
                        while (!feof($fp_in)) {
                            gzwrite($fp_out, fread($fp_in, 1024*512));
                        }

                        fclose($fp_in);
                    }

                    gzclose($fp_out);
                }
            }
        };

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

        $options = [
            'afterCopy' => static fn ($source, $destination) => $compressor->compress($destination),
        ];

        FileHelper::copyDirectory($source, $destination, $options);

        $this->assertFileExists($destination, 'Destination directory does not exist.');

        foreach ($files as $name => $content) {
            $destFile = $destination . '/' . $name;

            $this->assertFileExists($destFile . '.gz');
        }
    }

    public function testCallable(): void
    {
        $callable = new class ($this) {
            private TestCase $testCase;
            public bool $beforeCalled = false;
            public bool $afterCalled = false;

            public function __construct(TestCase $testCase)
            {
                $this->testCase = $testCase;
            }

            public function beforeCopy(string $source, string $destination): void
            {
                $this->beforeCalled = true;
                $this->testCase->assertFileDoesNotExist($destination);
            }

            public function afterCopy(string $source, string $destination): void
            {
                $this->afterCalled = true;
                $this->testCase->assertFileExists($destination);
            }
        };

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

        $options = [
            'beforeCopy' => [$callable, 'beforeCopy'],
            'afterCopy' => [$callable, 'afterCopy'],
        ];

        FileHelper::copyDirectory($source, $destination, $options);

        $this->assertTrue($callable->beforeCalled);
        $this->assertTrue($callable->afterCalled);
    }

    public function testException(): void
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

        $this->expectException(TypeError::class);

        FileHelper::copyDirectory($source, $destination, [
            'beforeCopy' => 'not_exists_callback',
        ]);

        FileHelper::copyDirectory($source, $destination, [
            'afterCopy' => 'not_exists_callback',
        ]);
    }
}

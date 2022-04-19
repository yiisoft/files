<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests;

use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Files\FileHelper;

final class FileHelperCopyFileTest extends FileSystemTestCase
{
    public function testBaseCopy(): void
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

        foreach ($files as $name => $content) {
            $sourceFile = $source . '/' . $name;
            $destFile = $destination . '/' . $name;

            FileHelper::copyFile($sourceFile, $destFile, ['dirMode' => 0755]);

            $this->assertFileExists($destFile);
        }
    }

    public function testCopyWithNoDir(): void
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

        foreach ($files as $name => $content) {
            $sourceFile = $source . '/' . $name;
            $destFile = $destination . '/' . $name;

            FileHelper::copyFile($sourceFile, $destFile);

            $this->assertFileExists($destFile);
        }
    }

    public function testCopyWithNoSource(): void
    {
        $source = 'test_src_dir';
        $files = [
            'file1.txt' => 'file 1 content',
            'file2.txt' => 'file 2 content',
        ];

        $basePath = $this->testFilePath;
        $source = $basePath . '/' . $source;
        $destination = $basePath . '/test_dst_dir';

        $this->expectException(InvalidArgumentException::class);

        foreach ($files as $name => $content) {
            $sourceFile = $source . '/' . $name;
            $destFile = $destination . '/' . $name;

            FileHelper::copyFile($sourceFile, $destFile);

            $this->assertFileDoesNotExist($destFile);
        }
    }
}

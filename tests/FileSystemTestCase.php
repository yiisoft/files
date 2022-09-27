<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Files\FileHelper;
use function is_array;

abstract class FileSystemTestCase extends TestCase
{
    /**
     * @var string Test files path.
     */
    protected string $testFilePath = '';

    public function setUp(): void
    {
        $this->testFilePath = FileHelper::normalizePath(realpath(sys_get_temp_dir()) . '/' . static::class);

        FileHelper::ensureDirectory($this->testFilePath, 0777);

        if (!file_exists($this->testFilePath)) {
            $this->markTestIncomplete('Unit tests runtime directory should have writable permissions.');
        }
    }

    public function tearDown(): void
    {
        FileHelper::removeDirectory($this->testFilePath);
    }

    /**
     * Creates test files structure.
     *
     * @param array $items file system objects to be created in format: objectName => objectContent
     * Arrays specifies directories, other values - files.
     * @param string|null $basePath structure base file path.
     */
    protected function createFileStructure(array $items, ?string $basePath = null): void
    {
        $basePath ??= $this->testFilePath;

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
     * Check if exist filename.
     */
    protected function checkExist(array $exist, string $dstDirName): void
    {
        foreach ($exist as $name => $content) {
            if (is_array($content)) {
                $this->checkExist($content, $dstDirName . '/' . $name);
            } else {
                $fileName = $dstDirName . '/' . $name;
                $this->assertFileExists($fileName);
                $this->assertStringEqualsFile($fileName, $content, 'Incorrect file content.');
            }
        }
    }

    /**
     * Check if no exist filename.
     */
    protected function checkNoexist(array $noexist, string $dstDirName): void
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
     */
    protected function assertFileMode(int $expectedMode, string $fileName, string $message = ''): void
    {
        $expectedMode = sprintf('%04o', $expectedMode);
        $this->assertEquals($expectedMode, $this->getMode($fileName), $message);
    }

    /**
     * Get file permission mode.
     *
     * @param string $file file name.
     *
     * @return string permission mode.
     */
    protected function getMode(string $file): string
    {
        return substr(sprintf('%04o', fileperms($file)), -4);
    }

    /**
     * Check if chmod works as expected.
     *
     * On remote file systems and vagrant mounts chmod returns true but file permissions are not set properly.
     */
    protected function isChmodReliable(): bool
    {
        $directory = $this->testFilePath . '/test_chmod';
        mkdir($directory);
        chmod($directory, 0700);
        $mode = $this->getMode($directory);
        rmdir($directory);

        return $mode === '0700';
    }

    protected function assertEqualsPaths(array $expected, array $actual): void
    {
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }
}

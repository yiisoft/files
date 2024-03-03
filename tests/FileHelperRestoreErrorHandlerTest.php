<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Files\Tests\Support\StubErrorHandler;

final class FileHelperRestoreErrorHandlerTest extends TestCase
{
    private StubErrorHandler $errorHandler;

    protected function setUp(): void
    {
        $this->errorHandler = new StubErrorHandler();
        set_error_handler($this->errorHandler);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }

    public function testOpenFile(): void
    {
        $fn = function () {
            try {
                FileHelper::openFile(__DIR__ . '/non-exist-file', 'r');
            } catch (RuntimeException) {
                return 42;
            }
            return 0;
        };

        $result = $fn();
        $this->assertSame(42, $result);
        $this->assertErrorHandler();
    }

    public function testEnsureDirectory(): void
    {
        $fn = function () {
            try {
                FileHelper::ensureDirectory((new \ReflectionClass($this))->getFileName());
            } catch (RuntimeException) {
                return 42;
            }
            return 0;
        };

        $result = $fn();
        $this->assertSame(42, $result);
        $this->assertErrorHandler();
    }

    public function testUnlink(): void
    {
        $fn = function () {
            try {
                FileHelper::unlink(__DIR__ . '/non-exist-file');
            } catch (RuntimeException) {
                return 42;
            }
            return 0;
        };

        $result = $fn();
        $this->assertSame(42, $result);
        $this->assertErrorHandler();
    }

    private function assertErrorHandler(): void
    {
        $errorHandler = set_error_handler(null);
        restore_error_handler();
        $this->assertSame($this->errorHandler, $errorHandler);
    }
}

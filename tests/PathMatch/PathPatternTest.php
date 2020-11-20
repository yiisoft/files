<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests\PathMatch;

use PHPUnit\Framework\TestCase;
use Yiisoft\Files\PathMatch\PathPattern;

final class PathPatternTest extends TestCase
{
    /**
     * Data provider for [[testMatchPath()]]
     * @return array test data.
     */
    public function dataMatch(): array
    {
        return [
            // base
            ['dir/*.jpg', 'var/dir/42.jpg', true],
            ['dir/*.jpg', 'abc/42.jpg', false],
            // case-sensitive
            ['dir/*.jpg', 'dir/42.jpg', true],
            ['dir/*.jpg', 'DIR/42.JPG', true],
            ['dir/*.jpg', 'dir/42.jpg', true, ['caseSensitive']],
            ['dir/*.jpg', 'DIR/42.JPG', false, ['caseSensitive']],
            // full path
            ['i/*.jpg', 'i/hello.jpg', true],
            ['i/*.jpg', 'dir/i/hello.jpg', true],
            ['i/*.jpg', 'i/hello.jpg', true, ['fullPath']],
            ['i/*.jpg', 'dir/i/hello.jpg', false, ['fullPath']],
            // not exact slashes
            ['i/*.jpg', 'i/hello.jpg', true],
            ['i/*.jpg', 'i/abc/hello.jpg', false],
            ['i/*.jpg', 'i/hello.jpg', true, ['notExactSlashes']],
            ['i/*.jpg', 'i/abc/hello.jpg', true, ['notExactSlashes']],
            // windows path
            ['i/*.jpg', 'i\hello.jpg', true],
        ];
    }

    /**
     * @dataProvider dataMatch
     *
     * @param string $pattern
     * @param string $string
     * @param bool $expectedResult
     * @param array $options
     */
    public function testMatch(string $pattern, string $string, bool $expectedResult, array $options = []): void
    {
        $pathPattern = $this->getPathPattern($pattern, $options);
        $this->assertSame($expectedResult, $pathPattern->match($string));
    }

    private function getPathPattern(string $pattern, array $options): PathPattern
    {
        $pathPattern = new PathPattern($pattern);
        if (in_array('caseSensitive', $options)) {
            $pathPattern = $pathPattern->caseSensitive();
        }
        if (in_array('fullPath', $options)) {
            $pathPattern = $pathPattern->withFullPath();
        }
        if (in_array('notExactSlashes', $options)) {
            $pathPattern = $pathPattern->withNotExactSlashes();
        }

        return $pathPattern;
    }

    public function testImmutability(): void
    {
        $original = new PathPattern('*');
        $this->assertNotSame($original, $original->caseSensitive());
        $this->assertNotSame($original, $original->withFullPath());
        $this->assertNotSame($original, $original->withNotExactSlashes());
        $this->assertNotSame($original, $original->onlyFiles());
        $this->assertNotSame($original, $original->onlyDirectories());
    }
}
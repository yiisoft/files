<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests\PathMatcher;

use Yiisoft\Files\PathMatcher\PathMatcher;
use Yiisoft\Files\Tests\FileSystemTestCase;

final class PathMatcherTest extends FileSystemTestCase
{
    public function testEmpty(): void
    {
        $matcher = (new PathMatcher())
            ->doNotCheckFilesystem();

        $this->assertTrue($matcher->match(''));
        $this->assertTrue($matcher->match('hello.png'));
    }

    public function testOnly(): void
    {
        $matcher = (new PathMatcher())
            ->doNotCheckFilesystem()
            ->only('**.jpg', '**.png');

        $this->assertTrue($matcher->match('hello.png'));
        $this->assertFalse($matcher->match('hello.gif'));
    }

    public function testExcept(): void
    {
        $matcher = (new PathMatcher())
            ->doNotCheckFilesystem()
            ->except('**.jpg', '**.png');

        $this->assertTrue($matcher->match('hello.gif'));
        $this->assertFalse($matcher->match('hello.png'));
    }

    public function testCallback(): void
    {
        $matcher = (new PathMatcher())
            ->doNotCheckFilesystem()
            ->callback(fn ($path) => false);

        $this->assertFalse($matcher->match('hello.png'));
    }

    public function testCaseSensitive(): void
    {
        $matcher = (new PathMatcher())
            ->doNotCheckFilesystem()
            ->caseSensitive()
            ->only('**.jpg');

        $this->assertTrue($matcher->match('hello.jpg'));
        $this->assertFalse($matcher->match('hello.JPG'));
    }

    public function testFullPath(): void
    {
        $matcher = (new PathMatcher())
            ->doNotCheckFilesystem()
            ->only('dir/*.jpg');

        $this->assertTrue($matcher->match('dir/42.jpg'));
        $this->assertFalse($matcher->match('var/dir/42.jpg'));
    }

    public function testWindowsFullPath(): void
    {
        $matcher = (new PathMatcher())
            ->doNotCheckFilesystem()
            ->only('dir\\*.jpg');

        $this->assertTrue($matcher->match('dir/42.jpg'));
        $this->assertFalse($matcher->match('var/dir/42.jpg'));
    }

    public function testNotExactSlashes(): void
    {
        $matcher = (new PathMatcher())
            ->doNotCheckFilesystem()
            ->only('dir/**.jpg');

        $this->assertTrue($matcher->match('dir/inner/42.jpg'));
    }

    public function testMatchDirectories(): void
    {
        $matcher = (new PathMatcher())
            ->doNotCheckFilesystem()
            ->only('**notes/');

        $this->assertTrue($matcher->match('dir/notes'));
        $this->assertFalse($matcher->match('dir/otes'));
    }

    public function testPathPattern(): void
    {
        $matcher = (new PathMatcher())
            ->doNotCheckFilesystem()
            ->only(
                '.png',
                '**.jpg'
            );

        $this->assertTrue($matcher->match('42.jpg'));
        $this->assertFalse($matcher->match('42.png'));
    }

    public function testCheckFilesystem(): void
    {
        $source = 'check-fs';

        $structure = [
            'part1' => [
                'intro.txt' => 'content',
            ],
            'part2' => [
                'intro.txt' => 'content',
            ],
            'how-to.txt' => 'content',
        ];

        $this->createFileStructure([
            $source => $structure,
        ]);

        $basePath = $this->testFilePath . '/' . $source;

        $matcher = (new PathMatcher())->only('part2/');
        $this->assertFalse($matcher->match($basePath . '/part1'));

        $matcher = (new PathMatcher())->only('part2/');
        $this->assertTrue($matcher->match($basePath . '/part1/intro.txt'));

        $matcher = (new PathMatcher())->doNotCheckFilesystem()->only('dir/');
        $this->assertFalse($matcher->match($basePath . '/how-to.txt'));
    }

    public function testImmutability(): void
    {
        $original = new PathMatcher();
        $this->assertNotSame($original, $original->caseSensitive());
        $this->assertNotSame($original, $original->doNotCheckFilesystem());
        $this->assertNotSame($original, $original->only('42.txt'));
        $this->assertNotSame($original, $original->except('42.txt'));
        $this->assertNotSame($original, $original->callback(fn ($path) => false));
    }
}

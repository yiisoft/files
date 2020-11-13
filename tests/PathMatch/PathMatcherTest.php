<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests\PathMatch;

use PHPUnit\Framework\TestCase;
use Yiisoft\Files\PathMatch\PathMatcher;
use Yiisoft\Files\PathMatch\PathPattern;

final class PathMatcherTest extends TestCase
{
    public function testEmpty(): void
    {
        $matcher = new PathMatcher();

        $this->assertTrue($matcher->match(''));
        $this->assertTrue($matcher->match('hello.png'));
    }

    public function testOnly(): void
    {
        $matcher = (new PathMatcher())->only('*.jpg', '*.png');

        // Immutable test
        $matcher->only('*.txt');

        $this->assertTrue($matcher->match('hello.png'));
        $this->assertFalse($matcher->match('hello.gif'));
    }

    public function testExcept(): void
    {
        $matcher = (new PathMatcher())->except('*.jpg', '*.png');

        // Immutable test
        $matcher->except('*.gif');

        $this->assertTrue($matcher->match('hello.gif'));
        $this->assertFalse($matcher->match('hello.png'));
    }

    public function testCallback(): void
    {
        $matcher = (new PathMatcher())->callback(fn ($path) => false);

        // Immutable test
        $matcher->callback(fn ($path) => true);

        $this->assertFalse($matcher->match('hello.png'));
    }

    public function testCaseSensitive(): void
    {
        $matcher = (new PathMatcher())
            ->caseSensitive()
            ->only('*.jpg');

        $this->assertTrue($matcher->match('hello.jpg'));
        $this->assertFalse($matcher->match('hello.JPG'));
    }

    public function testFullPath(): void
    {
        $matcher = (new PathMatcher())
            ->withFullPath()
            ->only('dir/*.jpg');

        $this->assertTrue($matcher->match('dir/42.jpg'));
        $this->assertFalse($matcher->match('var/dir/42.jpg'));
    }

    public function testPathPattern(): void
    {
        $matcher = (new PathMatcher())->only(
            (new PathPattern('.png'))->withFullPath(),
            '.jpg'
        );

        $this->assertTrue($matcher->match('42.jpg'));
        $this->assertFalse($matcher->match('42.png'));
    }

    public function testWindowsPath(): void
    {
        $matcher = (new PathMatcher())->only('bootstrap/css/*.css');

        $this->assertTrue($matcher->match('d:\project\bootstrap\css\main.css'));
    }

    public function testImmutability(): void
    {
        $original = new PathMatcher();
        $this->assertNotSame($original, $original->caseSensitive());
        $this->assertNotSame($original, $original->withFullPath());
        $this->assertNotSame($original, $original->only('42.txt'));
        $this->assertNotSame($original, $original->except('42.txt'));
        $this->assertNotSame($original, $original->callback(fn ($path) => false));
    }
}

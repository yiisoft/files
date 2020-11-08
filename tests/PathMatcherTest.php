<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Files\PathMatcher;
use Yiisoft\Strings\WildcardPattern;

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
        $matcher = (new PathMatcher())->only('*.jpg');
        $caseSensitive = $matcher->caseSensitive();
        $notCaseSensitive = $caseSensitive->notCaseSensitive();

        // Default PathMatcher not case sensitive
        $this->assertTrue($matcher->match('hello.JPG'));

        $this->assertFalse($caseSensitive->match('hello.JPG'));
        $this->assertTrue($notCaseSensitive->match('hello.JPG'));
    }

    public function testWildcard(): void
    {
        $matcher = (new PathMatcher())->only(
            new WildcardPattern('.png'),
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
}

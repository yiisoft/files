<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests\PathMatcher;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Files\PathMatcher\CompositeMatcher;
use Yiisoft\Files\PathMatcher\PathMatcherInterface;

final class CompositeMatcherTest extends TestCase
{
    public function anyProvider(): array
    {
        return [
            'true-true' => [
                [$this->getTrueMatcher(), $this->getTrueMatcher()], true,
            ],
            'true-false' => [
                [$this->getTrueMatcher(), $this->getFalseMatcher()], true,
            ],
            'false-true' => [
                [$this->getFalseMatcher(), $this->getTrueMatcher()], true,
            ],
            'false-false' => [
                [$this->getFalseMatcher(), $this->getFalseMatcher()], false,
            ],
            'null-false' => [
                [$this->getNullMatcher(), $this->getFalseMatcher()], false,
            ],
            'null-true' => [
                [$this->getNullMatcher(), $this->getTrueMatcher()], true,
            ],
            'null-null' => [
                [$this->getNullMatcher(), $this->getNullMatcher()], null,
            ],
        ];
    }

    /**
     * @dataProvider anyProvider
     */
    public function testAny(array $matchers, ?bool $expected): void
    {
        $matcher = CompositeMatcher::any(...$matchers);
        $this->assertSame($expected, $matcher->match(''));
    }

    public function testIncorrectAnyArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CompositeMatcher::any('incorrect');
    }

    public function allProvider(): array
    {
        return [
            'true-true' => [
                [$this->getTrueMatcher(), $this->getTrueMatcher()], true,
            ],
            'true-false' => [
                [$this->getTrueMatcher(), $this->getFalseMatcher()], false,
            ],
            'false-true' => [
                [$this->getFalseMatcher(), $this->getTrueMatcher()], false,
            ],
            'false-false' => [
                [$this->getFalseMatcher(), $this->getFalseMatcher()], false,
            ],
            'null-false' => [
                [$this->getNullMatcher(), $this->getFalseMatcher()], false,
            ],
            'null-true' => [
                [$this->getNullMatcher(), $this->getTrueMatcher()], true,
            ],
            'null-null' => [
                [$this->getNullMatcher(), $this->getNullMatcher()], null,
            ],
        ];
    }

    /**
     * @dataProvider allProvider
     */
    public function testAll(array $matchers, ?bool $expected): void
    {
        $matcher = CompositeMatcher::all(...$matchers);
        $this->assertSame($expected, $matcher->match(''));
    }

    public function testIncorrectAllArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CompositeMatcher::all('incorrect');
    }

    private function getTrueMatcher(): PathMatcherInterface
    {
        return new class() implements PathMatcherInterface {
            public function match(string $path): ?bool
            {
                return true;
            }
        };
    }

    private function getFalseMatcher(): PathMatcherInterface
    {
        return new class() implements PathMatcherInterface {
            public function match(string $path): ?bool
            {
                return false;
            }
        };
    }

    private function getNullMatcher(): PathMatcherInterface
    {
        return new class() implements PathMatcherInterface {
            public function match(string $path): ?bool
            {
                return null;
            }
        };
    }
}

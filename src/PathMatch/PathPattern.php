<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatch;

use Yiisoft\Strings\WildcardPattern;

final class PathPattern implements PathMatcherInterface
{
    private WildcardPattern $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = (new WildcardPattern($pattern))
            ->ignoreCase()
            ->withEnding();
    }

    public function caseSensitive(): self
    {
        $new = clone $this;
        $new->pattern = $this->pattern->ignoreCase(false);
        return $new;
    }

    public function withFullPath(): self
    {
        $new = clone $this;
        $new->pattern = $this->pattern->withEnding(false);
        return $new;
    }

    public function match(string $path): bool
    {
        return $this->pattern->match($path);
    }
}

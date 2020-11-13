<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatch;

use Yiisoft\Strings\WildcardPattern;

/**
 * A shell path pattern to match against.
 * Based on {@see WildcardPattern}.
 */
final class PathPattern implements PathMatcherInterface
{
    private WildcardPattern $pattern;

    /**
     * @param string $pattern The path pattern to match against.
     */
    public function __construct(string $pattern)
    {
        $this->pattern = (new WildcardPattern($pattern))
            ->withExactSlashes()
            ->ignoreCase()
            ->withEnding();
    }

    /**
     * Make pattern case sensitive.
     * @return self
     */
    public function caseSensitive(): self
    {
        $new = clone $this;
        $new->pattern = $this->pattern->ignoreCase(false);
        return $new;
    }

    /**
     * Match full path, not just ending of path.
     * @return self
     */
    public function withFullPath(): self
    {
        $new = clone $this;
        $new->pattern = $this->pattern->withEnding(false);
        return $new;
    }

    /**
     * Match `/` character with wildcards.
     * @return self
     */
    public function withNotExactSlashes(): self
    {
        $new = clone $this;
        $new->pattern = $this->pattern->withExactSlashes(false);
        return $new;
    }

    /**
     * Checks if the passed path would match the given shell path pattern.
     *
     * @param string $path The tested path.
     * @return bool Whether the path matches pattern or not.
     */
    public function match(string $path): bool
    {
        return $this->pattern->match($path);
    }
}

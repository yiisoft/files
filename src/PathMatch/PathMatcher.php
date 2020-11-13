<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatch;

final class PathMatcher implements PathMatcherInterface
{
    private ?array $only = null;
    private ?array $except = null;
    private ?array $callbacks = null;

    private bool $caseSensitive = false;
    private bool $matchFullPath = false;

    public function caseSensitive(): self
    {
        $new = clone $this;
        $new->caseSensitive = true;
        return $new;
    }

    public function withFullPath(): self
    {
        $new = clone $this;
        $new->matchFullPath = true;
        return $new;
    }

    /**
     * @param string|PathPattern ...$patterns
     * @return self
     */
    public function only(...$patterns): self
    {
        $new = clone $this;
        $new->only = $patterns;
        return $new;
    }

    /**
     * @param string|PathPattern ...$patterns
     * @return self
     */
    public function except(string ...$patterns): self
    {
        $new = clone $this;
        $new->except = $patterns;
        return $new;
    }

    public function callback(callable ...$callbacks): self
    {
        $new = clone $this;
        $new->callbacks = $callbacks;
        return $new;
    }

    public function match(string $path): bool
    {
        $path = str_replace('\\', '/', $path);

        if ($this->only !== null) {
            if (!$this->matchPathPatterns($path, $this->only)) {
                return false;
            }
        }

        if ($this->except !== null) {
            if ($this->matchPathPatterns($path, $this->except)) {
                return false;
            }
        }

        if ($this->callbacks !== null) {
            foreach ($this->callbacks as $callback) {
                if (!$callback($path)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param string $path
     * @param string|PathPattern[] $patterns
     * @return bool
     */
    private function matchPathPatterns(string $path, array $patterns): bool
    {
        $patterns = $this->makePathPatterns($patterns);

        foreach ($patterns as $pattern) {
            if ($pattern->match($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[]|PathPattern[] $patterns
     * @return PathPattern[]
     */
    private function makePathPatterns(array $patterns): array
    {
        $pathPatterns = [];
        foreach ($patterns as $pattern) {
            if ($pattern instanceof PathPattern) {
                $pathPatterns[] = $pattern;
                continue;
            }

            $pathPattern = new PathPattern($pattern);

            if ($this->caseSensitive) {
                $pathPattern = $pathPattern->caseSensitive();
            }

            if ($this->matchFullPath) {
                $pathPattern = $pathPattern->withFullPath();
            }

            $pathPatterns[] = $pathPattern;
        }
        return $pathPatterns;
    }
}

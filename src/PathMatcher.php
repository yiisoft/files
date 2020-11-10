<?php

declare(strict_types=1);

namespace Yiisoft\Files;

use Yiisoft\Strings\WildcardPattern;

final class PathMatcher
{
    private ?array $only = null;
    private ?array $except = null;
    private ?array $callbacks = null;

    private bool $caseSensitive = false;

    public function caseSensitive(): self
    {
        $new = clone $this;
        $new->caseSensitive = true;
        return $new;
    }

    /**
     * @param string|WildcardPattern ...$patterns
     * @return self
     */
    public function only(...$patterns): self
    {
        $new = clone $this;
        $new->only = $patterns;
        return $new;
    }

    /**
     * @param string|WildcardPattern ...$patterns
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
            if (!$this->matchWildcardPatterns($path, $this->only)) {
                return false;
            }
        }

        if ($this->except !== null) {
            if ($this->matchWildcardPatterns($path, $this->except)) {
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
     * @param string|WildcardPattern[] $patterns
     * @return bool
     */
    private function matchWildcardPatterns(string $path, array $patterns): bool
    {
        $patterns = $this->makeWildcardPatterns($patterns);

        foreach ($patterns as $pattern) {
            if ($pattern->match($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[]|WildcardPattern[] $patterns
     * @return WildcardPattern[]
     */
    private function makeWildcardPatterns(array $patterns): array
    {
        $wildcardPatterns = [];
        foreach ($patterns as $pattern) {
            if ($pattern instanceof WildcardPattern) {
                $wildcardPatterns[] = $pattern;
                continue;
            }

            $wildcardPattern = new WildcardPattern('*' . $pattern);

            if (!$this->caseSensitive) {
                $wildcardPattern = $wildcardPattern->ignoreCase();
            }

            $wildcardPatterns[] = $wildcardPattern;
        }
        return $wildcardPatterns;
    }
}

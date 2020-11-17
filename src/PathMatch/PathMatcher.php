<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatch;

use Yiisoft\Strings\StringHelper;

final class PathMatcher implements PathMatcherInterface
{
    /**
     * @var PathPattern[]|null
     */
    private ?array $only = null;

    /**
     * @var PathPattern[]|null
     */
    private ?array $except = null;

    /**
     * @var callable[]|null
     */
    private ?array $callbacks = null;

    private bool $caseSensitive = false;
    private bool $matchFullPath = false;
    private bool $matchSlashesExactly = true;
    private bool $checkFilesystem = true;

    /**
     * Make string patterns case sensitive.
     * @return self
     */
    public function caseSensitive(): self
    {
        $new = clone $this;
        $new->caseSensitive = true;
        return $new;
    }

    /**
     * Match string patterns as full path, not just ending of path.
     * @return self
     */
    public function withFullPath(): self
    {
        $new = clone $this;
        $new->matchFullPath = true;
        return $new;
    }

    /**
     * Match `/` character with wildcards in string patterns.
     * @return self
     */
    public function withNotExactSlashes(): self
    {
        $new = clone $this;
        $new->matchSlashesExactly = false;
        return $new;
    }

    public function notCheckFilesystem(): self
    {
        $new = clone $this;
        $new->checkFilesystem = false;
        return $new;
    }

    /**
     * Set list of patterns that the files or directories should match.
     * @param string|PathPattern ...$patterns
     * @return self
     */
    public function only(...$patterns): self
    {
        $new = clone $this;
        $new->only = $this->makePathPatterns($patterns);
        return $new;
    }

    /**
     * Set list of patterns that the files or directories should not match.
     * @param string|PathPattern ...$patterns
     * @return self
     */
    public function except(...$patterns): self
    {
        $new = clone $this;
        $new->except = $this->makePathPatterns($patterns);
        return $new;
    }

    /**
     * Set list of PHP callback that is called for each path.
     *
     * The signature of the callback should be: `function ($path)`, where `$path` refers the full path to be filtered.
     * The callback should return `true` if the passed path would match and `false` if it doesn't.
     *
     * @param callable ...$callbacks
     * @return self
     */
    public function callback(callable ...$callbacks): self
    {
        $new = clone $this;
        $new->callbacks = $callbacks;
        return $new;
    }

    /**
     * Checks if the passed path would match specified conditions.
     *
     * @param string $path The tested path.
     * @return bool|null Whether the path matches conditions or not.
     */
    public function match(string $path): ?bool
    {
        $path = str_replace('\\', '/', $path);

        if (!$this->matchOnly($path)) {
            return false;
        }

        if ($this->matchExcept($path)) {
            return false;
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
     * @return bool
     */
    private function matchOnly(string $path): bool
    {
        if ($this->only === null) {
            return true;
        }

        $hasFalse = false;
        $hasNull = false;

        foreach ($this->only as $pattern) {
            if ($pattern->match($path) === true) {
                return true;
            }
            if ($pattern->match($path) === false) {
                $hasFalse = true;
            }
            if ($pattern->match($path) === null) {
                $hasNull = true;
            }
        }

        if ($this->checkFilesystem) {
            if (is_file($path)) {
                return $hasFalse ? false : true;
            }
            if (is_dir($path)) {
                return $hasNull ? true : false;
            }
        }

        return false;
    }

    /**
     * @param string $path
     * @return bool
     */
    private function matchExcept(string $path): bool
    {
        if ($this->except === null) {
            return false;
        }

        foreach ($this->except as $pattern) {
            if ($pattern->match($path) === true) {
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

            $isDirectory = StringHelper::endsWith($pattern, '/');
            if ($isDirectory) {
                $pattern = StringHelper::substring($pattern, 0, -1);
            }

            $pathPattern = new PathPattern($pattern);

            if ($this->caseSensitive) {
                $pathPattern = $pathPattern->caseSensitive();
            }

            if ($this->matchFullPath) {
                $pathPattern = $pathPattern->withFullPath();
            }

            if (!$this->matchSlashesExactly) {
                $pathPattern = $pathPattern->withNotExactSlashes();
            }

            if ($this->checkFilesystem) {
                $pathPattern = $isDirectory ? $pathPattern->onlyDirectories() : $pathPattern->onlyFiles();
            }

            $pathPatterns[] = $pathPattern;
        }
        return $pathPatterns;
    }
}

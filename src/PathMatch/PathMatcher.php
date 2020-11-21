<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatch;

use Yiisoft\Strings\StringHelper;

/**
 * Path matcher based on {@see PathPattern} with the following logic:
 *  - process `only()`: if there is at least one match, then continue, else return `false`;
 *  - process `except()`: if there is at least one match, return `false`, else continue;
 *  - process `callback()`: if there is at least one not match, return `false`, else return `true`.
 *
 * As patterns can use any implementations of {@see PathMatcherInterface} or strings, which will be
 * converted to `PathPattern` according to the options.
 *
 * If the string ends in `/`, then `PathPattern` will be created with option {@see PathPattern::onlyDirectories()},
 * else with option {@see PathPattern::onlyFiles()}. You can disable this behavior by enable option
 * {@see PathMatcher::notCheckFilesystem()}.
 *
 * Other options:
 *  - {@see PathMatcher::caseSensitive()}: make string patterns case sensitive;
 *  - {@see PathMatcher::withFullPath()}: match string patterns as full path, not just ending of path;
 *  - {@see PathMatcher::withNotExactSlashes()}: match `/` character with wildcards in string patterns.
 *
 * Usage example:
 *
 * ```php
 * $matcher = (new PathMatcher())
 *     ->notCheckFilesystem()
 *     ->only('*.css', '*.js')
 *     ->except('theme.css');
 *
 * $matcher->match('/var/www/example.com/assets/css/main.css'); // true
 * $matcher->match('/var/www/example.com/assets/css/main.css.map'); // false
 * $matcher->match('/var/www/example.com/assets/css/theme.css'); // false
 * ```
 */
final class PathMatcher implements PathMatcherInterface
{
    /**
     * @var PathMatcherInterface[]|null
     */
    private ?array $only = null;

    /**
     * @var PathMatcherInterface[]|null
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
     * Note: applies only to string patterns.
     *
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
     * Note: applies only to string patterns.
     *
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
     * Note: applies only to string patterns.
     *
     * @return self
     */
    public function withNotExactSlashes(): self
    {
        $new = clone $this;
        $new->matchSlashesExactly = false;
        return $new;
    }

    /**
     * Match path only as string, do not check file or directory exists.
     * Note: applies only to string patterns.
     *
     * @return self
     */
    public function notCheckFilesystem(): self
    {
        $new = clone $this;
        $new->checkFilesystem = false;
        return $new;
    }

    /**
     * Set list of patterns that the files or directories should match.
     *
     * @param PathMatcherInterface|string ...$patterns
     *
     * @return self
     */
    public function only(...$patterns): self
    {
        $new = clone $this;
        $new->only = $this->prepareMatchers($patterns);
        return $new;
    }

    /**
     * Set list of patterns that the files or directories should not match.
     *
     * @param PathMatcherInterface|string ...$patterns
     *
     * @return self
     */
    public function except(...$patterns): self
    {
        $new = clone $this;
        $new->except = $this->prepareMatchers($patterns);
        return $new;
    }

    /**
     * Set list of PHP callback that is called for each path.
     *
     * The signature of the callback should be: `function ($path)`, where `$path` refers the full path to be filtered.
     * The callback should return `true` if the passed path would match and `false` if it doesn't.
     *
     * @param callable ...$callbacks
     *
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
     *
     * @return bool Whether the path matches conditions or not.
     */
    public function match(string $path): bool
    {
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
     *
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
     *
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
     * @param PathMatcherInterface[]|string[] $patterns
     *
     * @return PathMatcherInterface[]
     */
    private function prepareMatchers(array $patterns): array
    {
        $pathPatterns = [];
        foreach ($patterns as $pattern) {
            if ($pattern instanceof PathMatcherInterface) {
                $pathPatterns[] = $pattern;
                continue;
            }

            $isDirectoryPattern = StringHelper::endsWith($pattern, '/');
            if ($isDirectoryPattern) {
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
                $pathPattern = $isDirectoryPattern
                    ? $pathPattern->onlyDirectories()
                    : $pathPattern->onlyFiles();
            }

            $pathPatterns[] = $pathPattern;
        }
        return $pathPatterns;
    }
}

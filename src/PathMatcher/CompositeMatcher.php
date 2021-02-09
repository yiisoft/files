<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatcher;

use InvalidArgumentException;
use function get_class;
use function is_object;

/**
 * Composite matcher allows combining several matchers.
 */
final class CompositeMatcher implements PathMatcherInterface
{
    private bool $matchAny;

    /**
     * @var array PathMatcherInterface[]
     */
    private array $matchers;

    private function __construct(bool $matchAny, array $matchers)
    {
        foreach ($matchers as $matcher) {
            if (!$matcher instanceof PathMatcherInterface) {
                $type = is_object($matcher) ? get_class($matcher) : gettype($matcher);
                $message = sprintf(
                    'Matchers should contain instances of \Yiisoft\Files\PathMatcher\PathMatcherInterface, %s given.',
                    $type
                );
                throw new InvalidArgumentException($message);
            }
        }
        $this->matchers = $matchers;
        $this->matchAny = $matchAny;
    }

    /**
     * Get an instance of composite matcher that gives a match if any of sub-matchers match.
     *
     * @param mixed ...$matchers Matchers to check.
     *
     * @return static
     */
    public static function any(...$matchers): self
    {
        return new self(true, $matchers);
    }

    /**
     * Get an instance of composite matcher that gives a match only if all of sub-matchers match.
     *
     * @param mixed ...$matchers Matchers to check.
     *
     * @return static
     */
    public static function all(...$matchers): self
    {
        return new self(false, $matchers);
    }

    public function match(string $path): ?bool
    {
        $allNulls = true;

        /** @var PathMatcherInterface $matcher */
        foreach ($this->matchers as $matcher) {
            $match = $matcher->match($path);

            if ($match === null) {
                continue;
            }

            $allNulls = false;

            if ($this->matchAny && $match) {
                return true;
            }

            if (!$this->matchAny && !$match) {
                return false;
            }
        }

        return $allNulls ? null : !$this->matchAny;
    }
}

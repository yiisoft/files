<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatch;

interface PathMatcherInterface
{
    /**
     * Checks if the passed path would matched.
     *
     * @param string $path The tested path.
     * @return bool Whether the path matches or not.
     */
    public function match(string $path): bool;
}

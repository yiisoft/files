<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatch;

interface PathMatcherInterface
{
    /**
     * Checks if the path matches.
     *
     * @param string $path The tested path.
     * @return bool|null Whether the path matches or not.
     */
    public function match(string $path): ?bool;
}

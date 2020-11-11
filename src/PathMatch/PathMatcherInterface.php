<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatch;

interface PathMatcherInterface
{
    public function match(string $path): bool;
}

<?php

declare(strict_types=1);

namespace Yiisoft\Files\Tests\Support;

final class StubErrorHandler
{
    public function __invoke(): bool
    {
        return false;
    }
}

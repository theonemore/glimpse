<?php

declare(strict_types=1);

namespace Fw2\Mentalist\Builder\Context;

readonly class ContextName
{
    public function __construct(
        public string $fqcn,
        public string $alias,
    ) {
    }
}

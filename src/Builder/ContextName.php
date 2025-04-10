<?php

namespace Fw2\Mentalist\Builder;

readonly class ContextName
{
    public function __construct(
        public string $fqcn,
        public string $alias,
    ) {
    }
}

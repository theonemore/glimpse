<?php

namespace Fw2\Mentalist\Builder;

readonly class Attribute
{
    /**
     *
     * @param class-string $fqcn
     * @param array<int, mixed> $arguments
     */
    public function __construct(
        public string $fqcn,
        public array $arguments
    ) {
    }

    public function getInstance(): object
    {
        return new ($this->fqcn)(... $this->arguments);
    }
}

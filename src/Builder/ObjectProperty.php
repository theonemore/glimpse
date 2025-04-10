<?php

namespace Fw2\Mentalist\Builder;

use Fw2\Mentalist\Types\Type;

readonly class ObjectProperty
{
    public function __construct(
        public string $name,
        public ?Type $type,
        /** @var array<string, Attribute> */
        public array $attributes,
    ) {
    }

    public function clone(): static
    {
        return clone $this;
    }
}

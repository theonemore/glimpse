<?php

namespace Fw2\Mentalist\Builder\Aspect;

use Fw2\Mentalist\Builder\Attribute;

trait HasAttributes
{

    /** @var array<int, Attribute> */
    private array $attributes = [];

    public function getAttributes(?string $fqcn = null): array
    {
        if (!$fqcn) {
            return $this->attributes;
        }

        return array_values(array_filter($this->attributes, fn(Attribute $a) => $a->fqcn === $fqcn));
    }

    public function addAttribute(Attribute $attribute): static
    {
        $this->attributes[] = $attribute;

        return $this;
    }
}

<?php

namespace Fw2\Mentalist\Builder\Aspect;

use Fw2\Mentalist\Builder\Attribute;

interface HasAttributeContract
{
    /**
     * @param string|null $fqcn
     * @return array<int, Attribute>
     */
    public function getAttributes(?string $fqcn = null): array;

    public function addAttribute(Attribute $attribute): static;
}
<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Entity\Aspect;

use Fw2\Glimpse\Entity\Attribute;

interface HasAttributeContract
{
    /**
     * @param  string|null $fqcn
     * @return array<int, Attribute>
     */
    public function getAttributes(?string $fqcn = null): array;

    public function addAttribute(Attribute $attribute): static;
}

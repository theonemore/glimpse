<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Entity\Aspect;

use Fw2\Glimpse\Entity\Attribute;

trait HasAttributes
{
    /**
     * @var array<int, Attribute>
     */
    private array $attributes = [];

    /**
     * @param string|null $fqcn
     * @return array<int, Attribute>
     */
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

    public function getAttribute(string $fqcn): ?Attribute
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->fqcn === $fqcn) {
                return $attribute;
            }
        }

        return null;
    }

    public function hasAttribute(string $fqcn): bool
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->fqcn === $fqcn) {
                return true;
            }
        }

        return false;
    }
}

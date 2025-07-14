<?php

namespace Fw2\Glimpse\Types\Entity;

use Fw2\Glimpse\Types\ObjectType;

/**
 * @template T
 * @template-extends ObjectType<T>
 */
class ResourceType extends ObjectType
{
    public function isClosed(): bool
    {
        return !$this->isOpen();
    }

    private function isOpen(): bool
    {
        return $this->getValue();
    }
}

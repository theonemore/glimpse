<?php

namespace Fw2\Glimpse\Types\Entity;

use Fw2\Glimpse\Types\ObjectType;

use function Termwind\parse;

/**
 * @template T
 * @template-extends ObjectType<T>
 */
class ResourceType extends ObjectType
{

    public function __construct(?string $description = null)
    {
        parent::__construct('resource', $description);
    }

    public function isClosed(): bool
    {
        return !$this->isOpen();
    }

    private function isOpen(): bool
    {
        return $this->getValue();
    }
}

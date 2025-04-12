<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Entity;

use Fw2\Glimpse\Entity\Aspect\HasAttributeContract;
use Fw2\Glimpse\Entity\Aspect\HasAttributes;
use Fw2\Glimpse\Entity\Aspect\HasInfo;
use Fw2\Glimpse\Types\Type;

class ObjectProperty implements HasAttributeContract
{
    use HasAttributes;
    use HasInfo;

    public function __construct(
        public string $name,
        public ?Type $type,
    ) {
    }

    public function clone(): static
    {
        return clone $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

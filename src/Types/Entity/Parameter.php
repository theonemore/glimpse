<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types\Entity;

use Fw2\Glimpse\Types\Aspect\HasAttributeContract;
use Fw2\Glimpse\Types\Aspect\HasAttributes;
use Fw2\Glimpse\Types\Aspect\HasInfo;
use Fw2\Glimpse\Types\Type;

class Parameter implements HasAttributeContract
{
    use HasAttributes;
    use HasInfo;

    public function __construct(
        public string $name,
        public ?Type $type,
    ) {
    }

    public function copy(): self
    {
        return clone $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }
}

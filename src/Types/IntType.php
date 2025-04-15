<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

class IntType extends ScalarType
{
    public function __construct(
        readonly public ?int $min = null,
        readonly public ?int $max = null,
        ?string $description = null,
    ) {
        parent::__construct($description);
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function getName(): string
    {
        return 'int';
    }
}

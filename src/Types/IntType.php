<?php

declare(strict_types=1);

namespace Fw2\Mentalist\Types;

class IntType extends ScalarType
{
    public function __construct(
        readonly public ?int $min = null,
        readonly public ?int $max = null,
    ) {
        parent::__construct();
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }
}

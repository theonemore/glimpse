<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

class FloatType extends ScalarType
{
    public function __construct(
        readonly public ?float $min = null,
        readonly public ?float $max = null,
        ?string $description = null,
    ) {
        parent::__construct($description);
    }

    public function getName(): string
    {
        return 'float';
    }
}

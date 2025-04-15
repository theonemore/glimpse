<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

abstract class ScalarType extends Type
{
    public function isScalar(): bool
    {
        return true;
    }
}

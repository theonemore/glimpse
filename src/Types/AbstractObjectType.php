<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

class AbstractObjectType extends Type
{
    public function isScalar(): bool
    {
        return false;
    }
}

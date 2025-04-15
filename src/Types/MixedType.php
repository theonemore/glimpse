<?php

namespace Fw2\Glimpse\Types;

class MixedType extends Type
{
    public function isScalar(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return 'mixed';
    }
}

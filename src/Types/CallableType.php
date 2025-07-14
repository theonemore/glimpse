<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

class CallableType extends Type
{
    public function isScalar(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return 'callable';
    }
}

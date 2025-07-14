<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

class BoolType extends ScalarType
{
    public function getName(): string
    {
        return 'bool';
    }
}

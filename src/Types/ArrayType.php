<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

class ArrayType extends Type
{
    public function __construct(
        public readonly Type $of,
    ) {
        parent::__construct();
    }

    public function getOf(): Type
    {
        return $this->of;
    }
}

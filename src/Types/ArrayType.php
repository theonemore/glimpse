<?php

namespace Fw2\Mentalist\Types;

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
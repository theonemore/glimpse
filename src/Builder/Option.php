<?php

namespace Fw2\Mentalist\Builder;

use Fw2\Mentalist\Types\Type;

class Option extends Type
{
    public function __construct(public readonly Type $of)
    {
        parent::__construct();
    }

    public function getDescription(): ?string
    {
        return $this->of->getDescription();
    }

    public function getOf(): Type
    {
        return $this->of;
    }
}
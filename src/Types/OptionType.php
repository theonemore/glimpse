<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

class OptionType extends Type
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

    public function isScalar(): bool
    {
        return $this->of->isScalar();
    }
}

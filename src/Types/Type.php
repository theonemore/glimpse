<?php

declare(strict_types=1);

namespace Fw2\Mentalist\Types;

abstract class Type
{
    public function __construct(private readonly ?string $description = null)
    {
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}

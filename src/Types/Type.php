<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

abstract class Type
{
    protected mixed $value = null;
    private bool $materialized = false;

    public function __construct(
        private readonly ?string $description = null
    ) {
    }

    public function setValue(mixed $value): static
    {
        $this->materialized = true;
        $this->value = $value;
        return $this;
    }

    public function isMaterialized(): bool
    {
        return $this->materialized;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    abstract public function isScalar(): bool;

    abstract public function getName(): string;

    public function getValue(): mixed
    {
        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Entity\Aspect;

trait HasInfo
{
    protected ?string $description = null;
    protected ?string $summary = null;

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types\Aspect;

interface HasDescriptionContract
{
    public function setDescription(?string $description): static;

    public function getDescription(): ?string;

    public function getSummary(): ?string;

    public function setSummary(?string $summary): static;
}

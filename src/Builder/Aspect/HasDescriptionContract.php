<?php

namespace Fw2\Mentalist\Builder\Aspect;

interface HasDescriptionContract
{
    public function setDescription(?string $description): static;

    public function getDescription(): ?string;
}
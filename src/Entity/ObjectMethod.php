<?php

declare(strict_types=1);

namespace Fw2\Mentalist\Entity;

use Fw2\Mentalist\Builder\Aspect\HasAttributeContract;
use Fw2\Mentalist\Builder\Aspect\HasAttributes;
use Fw2\Mentalist\Builder\Aspect\HasDescriptionContract;
use Fw2\Mentalist\Builder\Aspect\HasInfo;
use Fw2\Mentalist\Types\Type;

class ObjectMethod implements HasAttributeContract, HasDescriptionContract
{
    use HasAttributes;
    use HasInfo;

    /**
     * @var array<string, Parameter>
     */
    private array $parameters = [];
    public ?Type $returnType = null;

    public function __construct(
        public readonly string $name,
    ) {
    }

    public function addParameter(Parameter $parameter): void
    {
        $this->parameters[$parameter->name] = $parameter;
    }

    public function setReturnType(?Type $type): static
    {
        $this->returnType = $type;

        return $this;
    }

    public function getReturnType(): ?Type
    {
        return $this->returnType;
    }

    /**
     * @return array<string, Parameter>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function withName(string $name): self
    {
        $method = (new self($name))
            ->setSummary($this->getSummary())
            ->setDescription($this->getDescription())
            ->setReturnType($this->returnType);

        foreach ($this->parameters as $parameter) {
            $method->addParameter($parameter->copy());
        }

        return $method;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

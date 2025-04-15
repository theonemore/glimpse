<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

use Fw2\Glimpse\Entity\Aspect\HasAttributeContract;
use Fw2\Glimpse\Entity\Aspect\HasAttributes;
use Fw2\Glimpse\Entity\ObjectMethod;
use Fw2\Glimpse\Entity\ObjectProperty;

class ObjectType extends Type implements HasAttributeContract
{
    use HasAttributes;

    /**
     * @var array<string, ObjectMethod>
     */
    private array $methods = [];

    /**
     * @var array<string, ObjectProperty>
     */
    private array $properties = [];

    public function __construct(
        private readonly string $fqcn,
        ?string $description = null,
    ) {
        parent::__construct($description);
    }

    public function addMethod(ObjectMethod $method): static
    {
        $this->methods[$method->name] = $method;

        return $this;
    }

    public function addProperty(ObjectProperty $property): static
    {
        $this->properties[$property->name] = $property;

        return $this;
    }

    public function hasSameMethod(ObjectMethod $method): bool
    {
        return array_key_exists($method->name, $this->methods);
    }

    public function hasSameProperty(ObjectProperty $property): bool
    {
        return array_key_exists($property->name, $this->properties);
    }

    /**
     * @return array<ObjectMethod>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return array<string, ObjectProperty>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(string $name): ?ObjectProperty
    {
        return $this->properties[$name] ?? null;
    }

    public function getMethod(string $name): ?ObjectMethod
    {
        return $this->methods[$name] ?? null;
    }

    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function isScalar(): bool
    {
        return false;
    }
}

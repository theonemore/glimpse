<?php

namespace Fw2\Mentalist\Builder;

use Fw2\Mentalist\Reflector;
use Fw2\Mentalist\Types\ObjectType;

class ObjectPromise extends ObjectType
{
    private Reflector $reflector;

    public function __construct(string $fqcn, Reflector $reflector)
    {
        parent::__construct($fqcn);
        $this->reflector = $reflector;
    }

    private function resolve(): ObjectType
    {
        return $this->reflector->reflect($this->getFqcn());
    }

    public function addMethod(ObjectMethod $method): void
    {
        $this->resolve()->addMethod($method);
    }

    public function addProperty(ObjectProperty $property): void
    {
        $this->resolve()->addProperty($property);
    }

    /**
     * @return array<ObjectMethod>
     */
    public function getMethods(): array
    {
        return $this->resolve()->getMethods();
    }

    public function getProperties(): array
    {
        return $this->resolve()->getProperties();
    }

    public function getProperty(string $name): ObjectProperty
    {
        return $this->resolve()->getProperty($name);
    }

    public function getMethod(string $name): ObjectMethod
    {
        return $this->resolve()->getMethod($name);
    }

    public function getAttributes(?string $fqcn = null): array
    {
        return $this->resolve()->getAttributes($fqcn);
    }

    public function addAttribute(Attribute $attribute): static
    {
        $this->resolve()->addAttribute($attribute);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->resolve()->getDescription();
    }
}

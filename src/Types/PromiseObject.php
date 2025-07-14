<?php

namespace Fw2\Glimpse\Types;

use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\Entity\Attribute;
use Fw2\Glimpse\Types\Entity\ObjectMethod;
use Fw2\Glimpse\Types\Entity\ObjectProperty;
use ReflectionException;

/**
 * @template T
 * @template-extends ObjectType<T>
 */
class PromiseObject extends ObjectType
{
    private Reflector $reflector;

    /**
     * @var Type[]
     */
    private array $implementations;

    /**
     * @param class-string $fqcn
     * @param Reflector $reflector
     * @param Type[] $implementations
     */
    public function __construct(string $fqcn, Reflector $reflector, array $implementations = [])
    {
        parent::__construct($fqcn);
        $this->reflector = $reflector;
        $this->implementations = $implementations;
    }

    /**
     * @return ObjectType<T>
     * @throws ReflectionException
     */
    public function resolve(): ObjectType
    {
        return $this->reflector->reflect($this->getFqcn(), $this->implementations);
    }

    public function addMethod(ObjectMethod $method): static
    {
        $this->resolve()->addMethod($method);

        return $this;
    }

    public function addProperty(ObjectProperty $property): static
    {
        $this->resolve()->addProperty($property);

        return $this;
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

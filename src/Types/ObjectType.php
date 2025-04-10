<?php

namespace Fw2\Mentalist\Types;

use Fw2\Mentalist\Builder\Aspect\HasAttributeContract;
use Fw2\Mentalist\Builder\Aspect\HasAttributes;
use Fw2\Mentalist\Builder\ObjectMethod;
use Fw2\Mentalist\Builder\ObjectProperty;

class ObjectType extends Type implements HasAttributeContract
{
    use HasAttributes;

    /** @var array<string, ObjectMethod> */
    private array $methods = [];

    /** @var array<string, ObjectProperty> */
    private array $properties = [];

    public function __construct(
        private readonly string $fqcn,
        ?string $description = null,
    ) {
        parent::__construct($description);
    }

    public function addMethod(ObjectMethod $method): void
    {
        $this->methods[$method->name] = $method;
    }

    public function addProperty(ObjectProperty $property): void
    {
        $this->properties[$property->name] = $property;
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

    public function getProperty(string $name): ObjectProperty
    {
        return $this->properties[$name];
    }

    public function getMethod(string $name): ObjectMethod
    {
        return $this->methods[$name];
    }

    public function getFqcn(): string
    {
        return $this->fqcn;
    }
}

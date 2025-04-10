<?php

namespace Fw2\Mentalist\Providers;

use Fw2\Mentalist\Builder\ClassBuilder;
use Fw2\Mentalist\Reflector;

class ClassBuilderProvider
{
    private ?ClassBuilder $classBuilder = null;

    public function __construct(
        readonly private AttributeBuilderProvider $attributeBuilderFactory,
        readonly private MethodBuilderProvider $methodBuilderFactory,
        readonly private PropertyBuilderProvider $propertyBuilderFactory,
    ) {
    }

    public function get(Reflector $reflector): ClassBuilder
    {
        return $this->classBuilder ?? $this->classBuilder = new ClassBuilder(
            $this->attributeBuilderFactory->create(),
            $this->methodBuilderFactory->get($reflector),
            $this->propertyBuilderFactory->get($reflector),
            $reflector,
        );
    }
}

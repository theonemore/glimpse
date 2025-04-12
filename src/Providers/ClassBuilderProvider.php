<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Providers;

use Fw2\Glimpse\Builder\ClassBuilder;
use Fw2\Glimpse\Reflector;

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

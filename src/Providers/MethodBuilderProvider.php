<?php

namespace Fw2\Mentalist\Providers;

use Fw2\Mentalist\Builder\DocBlockHelper;
use Fw2\Mentalist\Builder\MethodBuilder;
use Fw2\Mentalist\Reflector;

readonly class MethodBuilderProvider
{
    public function __construct(
        private TypeBuilderProvider $typeBuilderFactory,
        private AttributeBuilderProvider $attributeBuilderFactory,
        private DocBlockHelper $docHelper,
    ) {
    }

    public function get(Reflector $reflector): MethodBuilder
    {
        return new MethodBuilder(
            $this->typeBuilderFactory->get($reflector),
            $this->attributeBuilderFactory->create(),
            $this->docHelper,
        );
    }
}

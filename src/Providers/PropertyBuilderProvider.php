<?php

declare(strict_types=1);

namespace Fw2\Mentalist\Providers;

use Fw2\Mentalist\Builder\DocBlockHelper;
use Fw2\Mentalist\Builder\PropertyBuilder;
use Fw2\Mentalist\Reflector;

class PropertyBuilderProvider
{
    private ?PropertyBuilder $builder = null;

    public function __construct(
        readonly private TypeBuilderProvider $typeBuilderFactory,
        readonly private AttributeBuilderProvider $attributeBuilderFactory,
        readonly private DocBlockHelper $blockHelper,
    ) {
    }

    public function get(Reflector $reflector): PropertyBuilder
    {
        return $this->builder ?? $this->builder = new PropertyBuilder(
            $this->typeBuilderFactory->get($reflector),
            $this->attributeBuilderFactory->create(),
            $this->blockHelper,
        );
    }
}

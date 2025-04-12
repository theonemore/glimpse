<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Providers;

use Fw2\Glimpse\Builder\DocBlockHelper;
use Fw2\Glimpse\Builder\PropertyBuilder;
use Fw2\Glimpse\Reflector;

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

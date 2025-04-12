<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Providers;

use Fw2\Glimpse\Builder\DocBlockHelper;
use Fw2\Glimpse\Builder\MethodBuilder;
use Fw2\Glimpse\Reflector;

class MethodBuilderProvider
{
    private ?MethodBuilder $builder = null;

    public function __construct(
        readonly private TypeBuilderProvider $typeBuilderFactory,
        readonly private AttributeBuilderProvider $attributeBuilderFactory,
        readonly private DocBlockHelper $docHelper,
    ) {
    }

    public function get(Reflector $reflector): MethodBuilder
    {
        return $this->builder ?? $this->builder = new MethodBuilder(
            $this->typeBuilderFactory->get($reflector),
            $this->attributeBuilderFactory->create(),
            $this->docHelper,
        );
    }
}

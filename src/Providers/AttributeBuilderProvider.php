<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Providers;

use Fw2\Glimpse\Builder\AttributeBuilder;

class AttributeBuilderProvider
{
    private ?AttributeBuilder $builder = null;

    public function __construct(
        readonly private EvaluatorProvider $evaluators,
    ) {
    }

    public function create(): AttributeBuilder
    {
        return $this->builder ?? $this->builder = new AttributeBuilder($this->evaluators->get());
    }
}

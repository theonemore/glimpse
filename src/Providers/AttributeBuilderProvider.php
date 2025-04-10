<?php

namespace Fw2\Mentalist\Providers;

use Fw2\Mentalist\Builder\AttributeBuilder;

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

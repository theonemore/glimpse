<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Builders;

use Fw2\Glimpse\Context;
use Fw2\Glimpse\Types\Entity\Attribute;
use PhpParser\Node\Arg;
use PhpParser\Node\AttributeGroup;

class AttributeBuilder
{
    public function __construct(
        readonly private ScalarExpressionEvaluator $evaluator
    ) {
    }

    /**
     * @param AttributeGroup[] $attrGroups
     * @return Attribute[]
     */
    public function build(array $attrGroups, Context $ctx): array
    {
        $attributes = [];

        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                $attributes[] = new Attribute(
                    $ctx->resolve($attribute->name->name),
                    array_map(fn(Arg $arg) => $this->evaluator->evaluate($arg->value, $ctx), $attribute->args)
                );
            }
        }

        return $attributes;
    }
}

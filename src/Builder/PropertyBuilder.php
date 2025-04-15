<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Builder;

use Fw2\Glimpse\Context\Context;
use Fw2\Glimpse\Entity\ObjectProperty;
use PhpParser\Node\Stmt\Property;
use ReflectionException;

class PropertyBuilder
{
    public function __construct(
        readonly private TypeBuilder $tb,
        readonly private AttributeBuilder $ab,
        readonly private DocBlockHelper $docBlockHelper,
    ) {
    }

    /**
     * @param  Property $node
     * @param  Context  $ctx
     * @return ObjectProperty[]
     * @throws ReflectionException
     */
    public function build(Property $node, Context $ctx): array
    {
        $docBlock = $this->docBlockHelper->create($node->getDocComment()?->getText(), $ctx);

        $type = $this->tb->build(
            $this->docBlockHelper->getVarType($docBlock) ?? $node->type,
            $ctx
        );

        $attributes = $this->ab->build($node->attrGroups, $ctx);
        $properties = [];

        foreach ($node->props as $prop) {
            $property = new ObjectProperty(
                name: $prop->name->name,
                type: $type,
                className: $ctx->getStatic(),
            );

            foreach ($attributes as $attribute) {
                $property->addAttribute($attribute);
            }

            $properties[] = $property;
        }

        return $properties;
    }
}

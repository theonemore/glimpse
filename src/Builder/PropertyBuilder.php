<?php

declare(strict_types=1);

namespace Fw2\Mentalist\Builder;

use Fw2\Mentalist\Builder\Context\Context;
use Fw2\Mentalist\Entity\ObjectProperty;
use PhpParser\Node\Stmt\Property;
use ReflectionException;

readonly class PropertyBuilder
{
    public function __construct(
        private TypeBuilder $tb,
        private AttributeBuilder $ab,
        private DocBlockHelper $docBlockHelper,
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
            );

            foreach ($attributes as $attribute) {
                $property->addAttribute($attribute);
            }

            $properties[] = $property;
        }

        return $properties;
    }
}

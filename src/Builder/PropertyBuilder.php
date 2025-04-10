<?php

namespace Fw2\Mentalist\Builder;

use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
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
     * @param Property $node
     * @param Context $ctx
     * @return ObjectProperty[]
     * @throws ReflectionException
     */
    public function build(Property $node, Context $ctx): array
    {
        $docBlock = $this->docBlockHelper->create($node->getDocComment(), $ctx);

        $type = $this->tb->build(
            $this->docBlockHelper->getVarType($docBlock) ?? $node->type,
            $ctx
        );

        $attributes = $this->ab->build($node->attrGroups, $ctx);
        $properties = [];

        foreach ($node->props as $prop) {
            $properties[] = new ObjectProperty(
                name: $prop->name->name,
                type: $type,
                attributes: $attributes,
            );
        }

        return $properties;
    }
}

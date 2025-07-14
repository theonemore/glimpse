<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Builders;

use Exception;
use Fw2\Glimpse\Context;
use Fw2\Glimpse\PhpDoc\DocBlockHelper;
use Fw2\Glimpse\Types\Entity\ObjectProperty;
use PhpParser\Node\Stmt\Property;
use ReflectionException;

class PropertyBuilder
{
    public function __construct(
        readonly private DocTypeBuilder $docTypeBuilder,
        readonly private PhpTypeBuilder $phpTypeBuilder,
        readonly private AttributeBuilder $attributeBuilder,
        readonly private DocBlockHelper $docBlockHelper,
    ) {
    }

    /**
     * @param Property $node
     * @param Context $ctx
     * @return ObjectProperty[]
     * @throws ReflectionException|Exception
     */
    public function build(Property $node, Context $ctx): array
    {
        $docBlock = $this->docBlockHelper->create($node->getDocComment()?->getText());

        $type = $this->docTypeBuilder->build(
            $this->docBlockHelper->getVarType($docBlock),
            $ctx
        ) ?? $this->phpTypeBuilder->build($node->type, $ctx);

        $attributes = $this->attributeBuilder->build($node->attrGroups, $ctx);
        $properties = [];

        foreach ($node->props as $prop) {
            $property = new ObjectProperty(
                name: $prop->name->name,
                type: $type,
                className: $ctx->resolve('static'),
            );

            foreach ($attributes as $attribute) {
                $property->addAttribute($attribute);
            }

            $properties[] = $property;
        }

        return $properties;
    }
}

<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Builders;

use Fw2\Glimpse\Context;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\ObjectType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use ReflectionException;

class ObjectTypeBuilder
{
    public function __construct(
        readonly private AttributeBuilder $attributes,
        readonly private MethodBuilder $methods,
        readonly private PropertyBuilder $properties,
        readonly private Reflector $reflector,
    ) {
    }

    /**
     * @param Stmt\ClassLike $classLike
     * @param Context $ctx
     * @return ObjectType<mixed>
     * @throws ReflectionException
     */
    public function build(Stmt\ClassLike $classLike, Context $ctx): ObjectType
    {
        if ($classLike instanceof Stmt\Class_ && $classLike->extends !== null) {
            $ctx->setParent($ctx->resolve($classLike->extends->name));
        }

        $fqcn = $ctx->resolve($classLike->name->name);
        $object = new ObjectType($fqcn);

        foreach ($this->attributes->build($classLike->attrGroups, $ctx) as $attribute) {
            $object->addAttribute($attribute);
        }

        foreach ($classLike->getTraitUses() as $use) {
            /**
             * @var array<string, array<string, Identifier>> $adaptations
             */
            $adaptations = [];
            foreach ($use->adaptations as $adaptation) {
                if ($adaptation instanceof Alias) {
                    $tAlias = $ctx->resolve($adaptation->trait->name);
                    $adaptations[$tAlias][$adaptation->method->name] = $adaptation->newName;
                }
            }

            foreach ($use->traits as $trait) {
                $tFqcn = $ctx->resolve($trait->name);
                $ref = $this->reflector->getReflection($tFqcn);
                $this->mergeFromTrait($object, $ref, $adaptations[$tFqcn] ?? []);
            }
        }

        foreach ($classLike->getMethods() as $methodNode) {
            if (!$methodNode->isPublic()) {
                continue;
            }

            if (str_starts_with($methodNode->name->name, '__')) {
                continue;
            }

            $object->addMethod($this->methods->build($methodNode, $ctx));
        }

        foreach ($classLike->stmts as $stmt) {
            if ($stmt instanceof Property && $stmt->isPublic() && !$stmt->isStatic()) {
                foreach ($this->properties->build($stmt, $ctx) as $property) {
                    $object->addProperty($property);
                }
            }
        }

        if ($classLike instanceof Stmt\Class_ && $classLike->extends !== null) {
            $parentFqcn = $ctx->resolve($classLike->extends->name);
            $parent = $this->reflector->getReflection($parentFqcn);
            $this->mergeFromParent($object, $parent);
        }

        if ($classLike instanceof Stmt\Interface_) {
            foreach ($classLike->extends as $interface) {
                $parentFqcn = $ctx->resolve($interface->name);
                $parent = $this->reflector->getReflection($parentFqcn);
                $this->mergeFromParent($object, $parent);
            }
        }

        return $object;
    }


    /**
     * @param ObjectType<mixed> $into
     * @param ObjectType<mixed> $from
     * @param array<string, Identifier> $adaptations
     * @return void
     */
    public function mergeFromTrait(ObjectType $into, ObjectType $from, array $adaptations = []): void
    {
        foreach ($from->getProperties() as $property) {
            if (!$into->hasSameProperty($property)) {
                $into->addProperty(clone $property);
            }
        }

        foreach ($from->getMethods() as $method) {
            if (!$into->hasSameMethod($method)) {
                $adaptation = $adaptations[$method->name] ?? null;
                $name = $adaptation->name ?? $method->name;
                $into->addMethod($method->withName($name));
            }
        }

        foreach ($from->getAttributes() as $attribute) {
            $into->addAttribute(clone $attribute);
        }
    }

    /**
     * @param ObjectType<mixed> $into
     * @param ObjectType<mixed> $from
     * @return void
     */
    public function mergeFromParent(ObjectType $into, ObjectType $from): void
    {
        foreach ($from->getProperties() as $property) {
            if (!$into->hasSameProperty($property)) {
                $into->addProperty(clone $property);
            }
        }

        foreach ($from->getMethods() as $method) {
            if (!$into->hasSameMethod($method)) {
                $into->addMethod(clone $method);
            }
        }
    }
}

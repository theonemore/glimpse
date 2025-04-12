<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Builder;

use Fw2\Glimpse\Context\Context;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\ObjectType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use ReflectionException;

class ClassBuilder
{
    public function __construct(
        readonly private AttributeBuilder $attributes,
        readonly private MethodBuilder $methods,
        readonly private PropertyBuilder $properties,
        readonly private Reflector $reflector,
    ) {
    }

    /**
     * @throws ReflectionException
     */
    public function build(ClassLike $classLike, Context $ctx): ObjectType
    {
        if ($classLike instanceof Class_ && $classLike->extends !== null) {
            $ctx->setParent($ctx->fqcn($classLike->extends->name));
        }

        $fqcn = $ctx->fqcn($classLike->name->name);
        $object = new ObjectType($fqcn);

        // attributes
        foreach ($this->attributes->build($classLike->attrGroups, $ctx) as $attribute) {
            $object->addAttribute($attribute);
        }

        // traits and adaptations
        foreach ($classLike->getTraitUses() as $use) {
            /**
             * @var array<string, array<string, Identifier>> $adaptations
             */
            $adaptations = [];
            foreach ($use->adaptations as $adaptation) {
                if ($adaptation instanceof Alias) {
                    $tAlias = $ctx->fqcn($adaptation->trait->name);
                    $adaptations[$tAlias][$adaptation->method->name] = $adaptation->newName;
                }
            }

            foreach ($use->traits as $trait) {
                $tFqcn = $ctx->fqcn($trait->name);
                $ref = $this->reflector->reflect($tFqcn);
                $this->mergeFromTrait($object, $ref, $adaptations[$tFqcn] ?? []);
            }
        }

        // methods
        foreach ($classLike->getMethods() as $methodNode) {
            if (!$methodNode->isPublic()) {
                continue;
            }

            $object->addMethod($this->methods->build($methodNode, $ctx));
        }

        // props
        foreach ($classLike->stmts as $stmt) {
            if ($stmt instanceof Property && $stmt->isPublic() && !$stmt->isStatic()) {
                foreach ($this->properties->build($stmt, $ctx) as $property) {
                    $object->addProperty($property);
                }
            }
        }

        // extends for class
        if ($classLike instanceof Class_ && $classLike->extends !== null) {
            $parentFqcn = $ctx->fqcn($classLike->extends->name);
            $parent = $this->reflector->reflect($parentFqcn);
            $this->mergeFromParent($object, $parent);
        }

        // extends for interface
        if ($classLike instanceof Interface_) {
            foreach ($classLike->extends as $interface) {
                $parentFqcn = $ctx->fqcn($interface->name);
                $parent = $this->reflector->reflect($parentFqcn);
                $this->mergeFromParent($object, $parent);
            }
        }

        return $object;
    }


    /**
     * @param ObjectType $into
     * @param ObjectType $from
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

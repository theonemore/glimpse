<?php

declare(strict_types=1);

namespace Fw2\Mentalist\Builder;

use Fw2\Mentalist\Builder\Context\Context;
use Fw2\Mentalist\Entity\ObjectMethod;
use Fw2\Mentalist\Entity\Parameter;
use PhpParser\Node\Stmt\ClassMethod;
use ReflectionException;

readonly class MethodBuilder
{
    public function __construct(
        private TypeBuilder $types,
        private AttributeBuilder $attributes,
        private DocBlockHelper $docs,
    ) {
    }

    /**
     * @throws ReflectionException
     */
    public function build(ClassMethod $node, Context $ctx): ObjectMethod
    {
        $method = new ObjectMethod(name: $node->name->name);

        $doc = $this->docs->create($node->getDocComment()?->getText(), $ctx);

        $method->setSummary($this->docs->getSummary($doc))
            ->setDescription($this->docs->getDescription($doc))
            ->setReturnType(
                $this->types->build(
                    $this->docs->getReturnType($doc) ?? $node->getReturnType(),
                    $ctx,
                )
            );

        $parametersTypes = $this->docs->getParamTypes($doc);

        foreach ($node->getParams() as $param) {
            $doc = $this->docs->create($param->getDocComment()?->getText(), $ctx);
            $docType = $this->docs->getVarType($doc);

            $type = $this->types->build(
                $docType ?? $parametersTypes[$param->var->name] ?? $param->type,
                $ctx
            );

            $parameter = (new Parameter(name: $param->var->name, type: $type))
                ->setSummary($this->docs->getSummary($doc))
                ->setDescription($this->docs->getDescription($doc));

            foreach ($this->attributes->build($param->attrGroups, $ctx) as $attribute) {
                $parameter->addAttribute($attribute);
            }

            $method->addParameter($parameter);
        }

        return $method;
    }
}

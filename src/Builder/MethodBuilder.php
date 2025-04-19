<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Builder;

use Fw2\Glimpse\Context\Context;
use Fw2\Glimpse\Entity\ObjectMethod;
use Fw2\Glimpse\Entity\Parameter;
use PhpParser\Node\Stmt\ClassMethod;
use ReflectionException;

class MethodBuilder
{
    public function __construct(
        readonly private TypeBuilder $types,
        readonly private AttributeBuilder $attributes,
        readonly private DocBlockHelper $docs,
    ) {
    }

    /**
     * @throws ReflectionException
     */
    public function build(ClassMethod $node, Context $ctx): ObjectMethod
    {
        $method = new ObjectMethod(name: $node->name->name, className: $ctx->getStatic());

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
        $parametersDescriptions = $this->docs->getParamDescriptions($doc);

        foreach ($this->attributes->build($node->attrGroups, $ctx) as $attribute) {
            $method->addAttribute($attribute);
        }

        foreach ($node->getParams() as $param) {
            $doc = $this->docs->create($param->getDocComment()?->getText(), $ctx);
            $docType = $this->docs->getVarType($doc);

            $type = $this->types->build(
                $docType ?? $parametersTypes[$param->var->name] ?? $param->type,
                $ctx
            );

            $parameter = (new Parameter(name: $param->var->name, type: $type))
                ->setSummary($this->docs->getSummary($doc))
                ->setDescription(
                    $this->docs->getVarDescription($doc)?->render()
                    ?? ($parametersDescriptions[$param->var->name] ?? null)?->render()
                    ?? $param->var->name
                );

            foreach ($this->attributes->build($param->attrGroups, $ctx) as $attribute) {
                $parameter->addAttribute($attribute);
            }

            $method->addParameter($parameter);
        }

        return $method;
    }
}

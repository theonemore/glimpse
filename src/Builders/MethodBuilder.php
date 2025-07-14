<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Builders;

use Exception;
use Fw2\Glimpse\Context;
use Fw2\Glimpse\PhpDoc\DocBlockHelper;
use Fw2\Glimpse\Types\Entity\ObjectMethod;
use Fw2\Glimpse\Types\Entity\Parameter;
use Fw2\Glimpse\Types\Type;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use ReflectionException;

class MethodBuilder
{
    public function __construct(
        readonly private DocTypeBuilder $docTypes,
        readonly private PhpTypeBuilder $phpTypes,
        readonly private AttributeBuilder $attributes,
        readonly private DocBlockHelper $docs,
    ) {
    }

    /**
     * @throws ReflectionException|Exception
     */
    public function build(ClassMethod $node, Context $ctx): ObjectMethod
    {
        $method = new ObjectMethod(name: $node->name->name, className: $ctx->getStatic());

        $doc = $this->docs->create($node->getDocComment()?->getText());

        $method->setSummary($this->docs->getSummary($doc))
            ->setDescription($this->docs->getDescription($doc))
            ->setReturnType($this->buildReturnType($node, $doc, $ctx));

        $parametersTypes = $this->docs->getParamTypes($doc);
        $parametersDescriptions = $this->docs->getParamDescriptions($doc);

        foreach ($this->attributes->build($node->attrGroups, $ctx) as $attribute) {
            $method->addAttribute($attribute);
        }

        foreach ($node->getParams() as $param) {
            $doc = $this->docs->create($param->getDocComment()?->getText());
            $docType = $this->docs->getVarType($doc);

            $type = match (true) {
                isset($docType) => $this->docTypes->build($docType, $ctx),
                isset($parametersTypes['$' . $param->var->name]) => $this->docTypes->build(
                    $parametersTypes['$' . $param->var->name],
                    $ctx
                ),
                default => $this->phpTypes->build($param->type, $ctx),
            };

            $parameter = (new Parameter(name: $param->var->name, type: $type))
                ->setSummary($this->docs->getSummary($doc))
                ->setDescription(
                    $this->docs->getVarDescription($doc)
                    ?? ($parametersDescriptions[$param->var->name] ?? null)
                    ?? $param->var->name
                );

            foreach ($this->attributes->build($param->attrGroups, $ctx) as $attribute) {
                $parameter->addAttribute($attribute);
            }

            $method->addParameter($parameter);
        }

        return $method;
    }

    /**
     * @throws Exception
     */
    private function buildReturnType(ClassMethod $node, ?PhpDocNode $doc, Context $ctx): ?Type
    {
        return $this->docTypes->build($this->docs->getReturnType($doc), $ctx)
            ?? $this->phpTypes->build($node->getReturnType(), $ctx);
    }
}

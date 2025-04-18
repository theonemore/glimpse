<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Builder;

use Fw2\Glimpse\Context\Context;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Type as DocType;

class DocBlockHelper
{
    public function __construct(
        readonly private DocBlockFactoryInterface $factory
    ) {
    }

    public function create(?string $comment, Context $ctx): ?DocBlock
    {
        if (!$comment) {
            return null;
        }

        try {
            return $this->factory->create($comment, $ctx->toPhpDoc());
        } catch (\Throwable) {
            return null;
        }
    }

    public function getSummary(?DocBlock $doc): ?string
    {
        return $doc?->getSummary() ?: null;
    }

    public function getDescription(?DocBlock $doc): ?string
    {
        return $doc?->getDescription()?->getBodyTemplate() ?: null;
    }

    /**
     * @param DocBlock|null $doc
     * @return array<string, DocType>
     */
    public function getParamTypes(?DocBlock $doc): array
    {
        $result = [];
        foreach ($doc?->getTagsByName('param') ?? [] as $tag) {
            if ($tag instanceof Param && $tag->getType()) {
                $result[$tag->getVariableName()] = $tag->getType();
            }
        }

        return $result;
    }

    public function getReturnType(?DocBlock $doc): ?DocType
    {
        $type = null;

        foreach ($doc?->getTagsByName('return') ?? [] as $tag) {
            if ($tag instanceof Return_) {
                $type = $tag->getType();
            }
        }

        return $type;
    }

    public function getVarType(?DocBlock $doc): ?DocType
    {
        $type = null;

        foreach ($doc?->getTagsByName('var') ?? [] as $tag) {
            if ($tag instanceof Var_) {
                $type = $tag->getType();
            }
        }

        return $type;
    }

    public function getVarDescription(?DocBlock $doc): ?Description
    {
        $description = null;

        foreach ($doc?->getTagsByName('var') ?? [] as $tag) {
            if ($tag instanceof Var_) {
                $description = $tag->getDescription();
            }
        }

        return $description;
    }

    /**
     * @param DocBlock|null $doc
     * @return array<string, Description>
     */
    public function getParamDescriptions(?DocBlock $doc): array
    {
        $result = [];

        foreach ($doc?->getTagsByName('param') ?? [] as $tag) {
            if ($tag instanceof Param) {
                $result[$tag->getVariableName()] = $tag->getDescription();
            }
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace Fw2\Glimpse\PhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;

class DocBlockHelper
{
    public function __construct(
        readonly private PhpDocParser $parser,
        readonly private Lexer $lexer,
    ) {
    }

    public function create(?string $comment): ?PhpDocNode
    {
        if (!$comment) {
            return null;
        }

        try {
            return $this->parser->parse(new TokenIterator($this->lexer->tokenize($comment)));
        } catch (\Throwable) {
            return null;
        }
    }

    public function getSummary(?PhpDocNode $doc): ?string
    {
        $summary = null;

        if ($doc) {
            foreach ($doc->children as $child) {
                if (!$child instanceof PhpDocTextNode) {
                    continue;
                }

                $l = mb_strpos($child->text, "\n");

                if ($l !== false) {
                    $summary = mb_substr($child->text, 0, $l);
                }

                break;
            }
        }

        return $summary;
    }

    public function getDescription(?PhpDocNode $doc): ?string
    {
        $description = null;

        if ($doc) {
            foreach ($doc->children as $child) {
                if (!$child instanceof PhpDocTextNode) {
                    continue;
                }

                $l = mb_strpos($child->text, "\n");

                if ($l !== false) {
                    $description = trim(mb_substr($child->text, $l));
                }

                break;
            }
        }

        return $description;
    }

    /**
     * @param PhpDocNode|null $doc
     * @return array<string, TypeNode>
     */
    public function getParamTypes(?PhpDocNode $doc): array
    {
        $result = [];
        if ($doc) {
            foreach ($doc->getTagsByName('@param') as $tag) {
                $value = $tag->value;
                if ($value instanceof ParamTagValueNode) {
                    $result[$value->parameterName] = $value->type;
                }
            }
        }

        return $result;
    }

    public function getReturnType(?PhpDocNode $doc): ?TypeNode
    {
        $type = null;

        foreach ($doc?->getTagsByName('@return') ?? [] as $tag) {
            $value = $tag->value;
            if ($value instanceof ReturnTagValueNode) {
                $type = $value->type;
            }
        }

        return $type;
    }

    public function getVarType(?PhpDocNode $doc): ?TypeNode
    {
        $type = null;

        foreach ($doc?->getTagsByName('@var') ?? [] as $tag) {
            $value = $tag->value;
            if ($value instanceof VarTagValueNode) {
                $type = $value->type;
            }
        }

        return $type;
    }

    public function getVarDescription(?PhpDocNode $doc): ?string
    {
        $description = null;

        foreach ($doc?->getTagsByName('@var') ?? [] as $tag) {
            $value = $tag->value;
            if ($value instanceof VarTagValueNode) {
                $description = $value->description;
            }
        }

        return $description;
    }

    /**
     * @param PhpDocNode|null $doc
     * @return array<string, string>
     */
    public function getParamDescriptions(?PhpDocNode $doc): array
    {
        $descriptions = [];

        foreach ($doc?->getTagsByName('@param') ?? [] as $tag) {
            $value = $tag->value;
            if ($value instanceof ParamTagValueNode) {
                $descriptions[$value->parameterName] = $value->description;
            }
        }

        return $descriptions;
    }
}

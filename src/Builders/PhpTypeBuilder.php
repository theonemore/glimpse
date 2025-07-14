<?php

namespace Fw2\Glimpse\Builders;

use Exception;
use Fw2\Glimpse\Context;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\ArrayType;
use Fw2\Glimpse\Types\BoolType;
use Fw2\Glimpse\Types\CallableType;
use Fw2\Glimpse\Types\FloatType;
use Fw2\Glimpse\Types\IntersectionType;
use Fw2\Glimpse\Types\IntType;
use Fw2\Glimpse\Types\MixedType;
use Fw2\Glimpse\Types\NullType;
use Fw2\Glimpse\Types\ObjectType;
use Fw2\Glimpse\Types\OptionType;
use Fw2\Glimpse\Types\StringType;
use Fw2\Glimpse\Types\Type;
use Fw2\Glimpse\Types\UnionType;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use RuntimeException;

class PhpTypeBuilder
{
    private Reflector $reflector;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    /**
     * @throws Exception
     */
    public function build(?Node $type, Context $context): ?Type
    {
        return match (true) {
            is_null($type) => new NullType(),
            $type instanceof Identifier => match ($type->name) {
                'string' => new StringType(),
                'int' => new IntType(),
                'float' => new FloatType(),
                'void', 'null' => new NullType(),
                'self', 'static' => $this->reflector->getReflection($context->getStatic()),
                'array', 'iterable' => new ArrayType(new MixedType()),
                'callable' => new CallableType(),
                'mixed' => new MixedType(),
                'bool', 'true', 'false' => new BoolType(),
                'object' => new ObjectType('stdClass'),
                'parent' => $this->reflector->getReflection($context->getParent()),
                default => throw new RuntimeException('Unsupported type: ' . $type->name),
            },
            $type instanceof Node\NullableType => new OptionType($this->build($type->type, $context)),
            $type instanceof Node\Name\FullyQualified,
                $type instanceof Node\Name => $this->reflector->getReflection($context->resolve($type->toCodeString())),
            $type instanceof Node\UnionType => new UnionType(
                ...array_map(fn(Node $t) => $this->build($t, $context), $type->types)
            ),
            $type instanceof Node\IntersectionType => new IntersectionType(
                ...array_map(fn(Node $t) => $this->build($t, $context), $type->types)
            ),
            default => throw new Exception('Unknown type ' . $type->getType()),
        };
    }
}

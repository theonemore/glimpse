<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Builder;

use Fw2\Glimpse\Context\Context;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\AbstractObjectType;
use Fw2\Glimpse\Types\ArrayType;
use Fw2\Glimpse\Types\BoolType;
use Fw2\Glimpse\Types\CallableType;
use Fw2\Glimpse\Types\FloatType;
use Fw2\Glimpse\Types\IntType;
use Fw2\Glimpse\Types\MixedType;
use Fw2\Glimpse\Types\NullType;
use Fw2\Glimpse\Types\OptionType;
use Fw2\Glimpse\Types\StringType;
use Fw2\Glimpse\Types\Type;
use Fw2\Glimpse\Types\UnionType;
use phpDocumentor\Reflection\PseudoTypes\ArrayShape;
use phpDocumentor\Reflection\PseudoTypes\IntegerRange;
use phpDocumentor\Reflection\PseudoTypes\NegativeInteger;
use phpDocumentor\Reflection\PseudoTypes\ObjectShape;
use phpDocumentor\Reflection\PseudoTypes\PositiveInteger;
use phpDocumentor\Reflection\Type as DocType;
use phpDocumentor\Reflection\Types\AbstractList;
use phpDocumentor\Reflection\Types\AggregatedType;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Callable_;
use phpDocumentor\Reflection\Types\Expression;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\InterfaceString;
use phpDocumentor\Reflection\Types\Mixed_;
use phpDocumentor\Reflection\Types\Null_;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\Parent_;
use phpDocumentor\Reflection\Types\Scalar;
use phpDocumentor\Reflection\Types\Self_;
use phpDocumentor\Reflection\Types\Static_;
use phpDocumentor\Reflection\Types\String_;
use phpDocumentor\Reflection\Types\Void_;
use phpDocumentor\Reflection\Types\This as This_;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use ReflectionException;
use RuntimeException;

class TypeBuilder
{
    private Reflector $reflector;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    /**
     * @throws ReflectionException
     */
    public function build(Node|DocType|null $type, ?Context $ctx = null): ?Type
    {
        return match (true) {
            $type instanceof Identifier => match ($type->name) {
                'string' => new StringType(),
                'int' => new IntType(),
                'float' => new FloatType(),
                'void', 'null' => new NullType(),
                'self', 'static' => $this->reflector->reflect($ctx->getStatic(), true),
                'iterable' => new ArrayType(new MixedType()),
                'callable' => new CallableType(),
                'mixed' => new MixedType(),
                default => throw new RuntimeException('Unsupported type: ' . $type->name),
            },
            $type instanceof Node\NullableType => new OptionType($this->build($type->type, $ctx)),
            $type instanceof Name\FullyQualified => $this->reflector->reflect($type->toString()),
            $type instanceof Name => $this->reflector->reflect($ctx->fqcn($type->toString())),
            $type instanceof DocType => $this->buildByDocType($type, $ctx),
            default => null,
        };
    }

    /**
     * @throws ReflectionException
     */
    private function buildByDocType(DocType $type, ?Context $ctx = null): Type
    {
        return match (true) {
            $type instanceof Expression => $this->buildByDocType($type->getValueType(), $ctx),
            $type instanceof ArrayShape,
                $type instanceof ObjectShape,
                $type instanceof Object_ => new AbstractObjectType(),

            $type instanceof Float_ => new FloatType(),

            $type instanceof Integer => match (true) {
                $type instanceof IntegerRange => new IntType(
                    min: (int)$type->getMinValue(),
                    max: (int)$type->getMaxValue()
                ),
                $type instanceof NegativeInteger => new IntType(max: 0),
                $type instanceof PositiveInteger => new IntType(min: 0),
                default => new IntType(),
            },

            $type instanceof String_,
                $type instanceof InterfaceString => new StringType(),

            $type instanceof AbstractList => new ArrayType($this->build($type->getValueType())),

            // TODO: Option of union, если есть Null?
            $type instanceof AggregatedType => new UnionType(
                ...array_map(fn(DocType $t) => $this->build($t, $ctx), iterator_to_array($type->getIterator()))
            ),

            $type instanceof Scalar => new UnionType(
                new IntType(),
                new FloatType(),
                new StringType(),
                new BoolType()
            ),

            $type instanceof Boolean => new BoolType(),

            $type instanceof Callable_ => new CallableType(),

            $type instanceof Nullable => new OptionType($this->build($type->getActualType(), $ctx)),

            $type instanceof Null_, $type instanceof Void_ => new NullType(),
            $type instanceof Mixed_ => new MixedType(),

            $type instanceof Self_,
                $type instanceof Static_,
                $type instanceof This_ => $this->reflector->reflect($ctx->getStatic(), true),

            $type instanceof Parent_ => $this->reflector->reflect($ctx->getParent()),

            default => throw new RuntimeException(sprintf('Unsupported type: %s', get_class($type))),
        };
    }
}

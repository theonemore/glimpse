<?php

namespace Fw2\Glimpse\Builders;

use Exception;
use Fw2\Glimpse\Context;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\ArrayType;
use Fw2\Glimpse\Types\BoolType;
use Fw2\Glimpse\Types\CallableType;
use Fw2\Glimpse\Types\DictType;
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
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayItemNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeForParameterNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\InvalidTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\OffsetAccessTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use ReflectionException;
use stdClass;

class DocTypeBuilder
{
    private Reflector $reflector;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    /**
     * @param TypeNode|null $type
     * @param Context $context
     * @return ($type is null ? null : Type)
     * @throws Exception
     */
    public function build(?TypeNode $type, Context $context): ?Type
    {
        return match (true) {
            is_null($type) => null,
            $type instanceof ObjectShapeNode => $this->buildObjectShape($type, $context),
            $type instanceof ConditionalTypeForParameterNode,
                $type instanceof ConditionalTypeNode => $this->buildConditionalType($type, $context),
            $type instanceof ArrayShapeNode => $this->buildArrayShape($type, $context),
            $type instanceof ArrayTypeNode => $this->buildArrayType($type, $context),
            $type instanceof GenericTypeNode => $this->buildGenericType($type, $context),
            $type instanceof UnionTypeNode => new UnionType(
                ...array_map(fn(TypeNode $tn) => $this->build($tn, $context), $type->types)
            ),
            $type instanceof IntersectionTypeNode => new IntersectionType(
                ...array_map(fn(TypeNode $in) => $this->build($in, $context), $type->types)
            ),
            $type instanceof IdentifierTypeNode => $this->buildIdentifier($type, $context),
            $type instanceof NullableTypeNode => $this->buildNullable($type, $context),
            $type instanceof ConstTypeNode => $this->buildConstType($type, $context),
            $type instanceof CallableTypeNode => new CallableType(),
            $type instanceof ThisTypeNode => $this->reflector->getReflection($context->getStatic()),
            $type instanceof InvalidTypeNode => throw new Exception('Invalid type'),
            default => throw new Exception('Unknown type: ' . $type->__toString()),
        };
    }

    /**
     * @param ObjectShapeNode $type
     * @param Context $context
     * @return ObjectType<stdClass>
     * @throws Exception
     */
    private function buildObjectShape(ObjectShapeNode $type, Context $context): ObjectType
    {
        $values = [];

        foreach ($type->items as $item) {
            $value = $this->build($item->valueType, $context)->getValue();

            $buildable = match (true) {
                $item->keyName instanceof ConstExprStringNode => $this->buildExpression($item->keyName, $context),
                default => $this->build($item->keyName, $context),
            };

            $values[$buildable->getValue()] = $value;
        }

        return (new ObjectType('stdClass'))->setValue((object)$values);
    }

    /**
     * @throws Exception
     */
    private function buildConditionalType(
        ConditionalTypeForParameterNode|ConditionalTypeNode $type,
        Context $context,
    ): UnionType {
        return new UnionType(
            $this->build($type->targetType, $context),
            $this->build($type->else, $context)
        );
    }

    /**
     * @param ArrayShapeNode $type
     * @param Context $context
     * @return ObjectType<stdClass>|ArrayType
     * @throws Exception
     */
    private function buildArrayShape(ArrayShapeNode $type, Context $context): ObjectType|ArrayType
    {
        $values = [];

        foreach ($type->items as $item) {
            $value = $this->build($item->valueType, $context)->getValue();
            $key = $item->keyName;
            $key
                ? $values[$this->build($key, $context)->getValue()] = $value
                : $values[] = $value;
        }


        if (array_is_list($values)) {
            return (new ArrayType(new MixedType()))->setValue($values);
        }

        return (new ObjectType('stdClass'))->setValue((object)$values);
    }

    /**
     * @throws Exception
     */
    private function buildArrayType(ArrayTypeNode $type, Context $context): ArrayType
    {
        return new ArrayType($this->build($type->type, $context));
    }

    private function buildGenericType(GenericTypeNode $type, Context $context): Type
    {
        return match (true) {
            $type->type->name == 'object' => $this->buildGenericDictType($type, $context),
            $type->type->name == 'string' => $this->buildGenericString($type, $context),
            $type->type->name == 'int' => $this->buildGenericInt($type, $context),
            $type->type->name == 'array' => match (count($type->genericTypes)) {
                1 => new ArrayType($this->build($type->genericTypes[0], $context)),
                2 => $this->buildGenericDictType($type, $context),
                default => throw new Exception('Unimplemented type'),
            },
            default => $this->buildGenericObjectType($type, $context),
        };
    }

    /**
     * @throws ReflectionException
     */
    private function buildIdentifier(IdentifierTypeNode $type, Context $context): Type
    {
        return $context->getImplementation($type->name) ?? match ($type->name) {
            'bool', 'false' => (new BoolType())->setValue(false),
            'true' => (new BoolType())->setValue(true),
            'null' => (new NullType())->setValue(null),
            'array' => (new ArrayType(new MixedType()))->setValue([]),
            'int' => (new IntType())->setValue(0),
            'float' => (new FloatType())->setValue(0),
            'string' => (new StringType())->setValue(''),
            'void' => new NullType(),
            'self', 'static' => $this->reflector->getReflection($context->getStatic()),
            'callable' => new CallableType(),
            'object' => new DictType(),
            'positive-int' => (new IntType(1)),
            'negative-int' => new IntType(null, -1),
            'non-positive-int' => new IntType(null, 0),
            'non-negative-int' => new IntType(0),
            'non-zero-int' => new UnionType(new IntType(null, -1), new IntType(1)),
            default => $this->buildObjectType($type->name, $context),
        };
    }

    /**
     * @throws Exception
     */
    private function buildNullable(NullableTypeNode $type, Context $context): OptionType
    {
        return new OptionType($this->build($type->type, $context));
    }

    /**
     * @throws Exception
     */
    private function buildConstType(ConstTypeNode $type, Context $context): Type
    {
        return $this->buildExpression($type->constExpr, $context);
    }

    /**
     * @throws Exception
     */
    private function buildExpression(ConstExprNode $expr, Context $context): Type
    {
        try {
            return match (true) {
                $expr instanceof ConstExprArrayNode => $this->buildArrayExprNode($expr, $context),
                $expr instanceof ConstExprIntegerNode => (new IntType())->setValue(eval('return ' . $expr . ';')),
                $expr instanceof ConstExprFloatNode => (new FloatType())->setValue(eval((string)$expr)),
                $expr instanceof ConstExprNullNode => (new NullType())->setValue(null),
                $expr instanceof ConstExprStringNode => (new StringType())->setValue(eval('return ' . $expr . ';')),
                $expr instanceof ConstFetchNode => $this->buildConstFetch($expr, $context),
                $expr instanceof ConstExprFalseNode => (new BoolType())->setValue(false),
                $expr instanceof ConstExprTrueNode => (new BoolType())->setValue(true),
                $expr instanceof ConstExprArrayItemNode => throw new Exception('Unimplemented type'),
                default => throw new Exception('Unimplemented type'),
            };
        } catch (\Throwable $e) {
            dd($expr, $e->getMessage(), (string)$expr);
        }
    }

    /**
     * @throws Exception
     */
    private function buildArrayExprNode(ConstExprArrayNode $constExpr, Context $context): Type
    {
        $values = [];

        foreach ($constExpr->items as $item) {
            $value = $this->buildExpression($item->value, $context)->getValue();
            $key = $item->key;
            $key
                ? $values[$this->buildExpression($item->key, $context)->getValue()] = $value
                : $values[] = $value;
        }


        if (array_is_list($values)) {
            return (new ArrayType(new MixedType()))->setValue($values);
        }

        return (new ObjectType('stdClass'))->setValue((object)$values);
    }

    private function buildConstFetch(ConstFetchNode $expr, Context $context): Type
    {
        $class = $context->resolve($expr->className);
        // TODO: нужно ли извлечь тип из константы?
        // Может нужно взять рефлексию от константы и получить тип?
        $value = constant(sprintf('%s::%s', $class, $expr->name));

        return match (gettype($value)) {
            'boolean' => (new BoolType())->setValue($value),
            'integer' => (new IntType())->setValue($value),
            'double' => (new FloatType())->setValue($value),
            'string' => (new StringType())->setValue($value),
            'array' => (new ArrayType(new MixedType()))->setValue($value),
            'NULL' => (new NullType())->setValue(null),
            default => throw new Exception('Unimplemented type ' . gettype($value)),
        };
    }

    /**
     * @param string $name
     * @param Context $context
     * @return ObjectType<mixed>
     * @throws ReflectionException
     */
    private function buildObjectType(string $name, Context $context): ObjectType
    {
        $name = $context->resolve($name);
        return $this->reflector->getReflection($name);
    }

    private function buildGenericDictType(GenericTypeNode $type, Context $context): DictType
    {
        [$keyType, $valueType] = count($type->genericTypes) === 1
            ? [null, $type->genericTypes[0]]
            : [$type->genericTypes[0], $type->genericTypes[1]];

        [$key, $value] = [$this->build($keyType, $context), $this->build($valueType, $context)];

        // Здесь стэн не может понять, то там конкретно только строка или инт.
        // @phpstan-ignore-next-line
        return new DictType($value, $key);
    }

    private function buildGenericInt(GenericTypeNode $type, Context $context): IntType
    {
        [$minType, $maxType] = count($type->genericTypes) === 1
            ? [null, $type->genericTypes[0]]
            : [$type->genericTypes[0], $type->genericTypes[1]];

        [$min, $max] = [$this->build($minType, $context), $this->build($maxType, $context)];

        return new IntType(
            $min->getValue(),
            $max->getValue(),
        );
    }

    private function buildGenericString(GenericTypeNode $type, Context $context): StringType
    {
        [$minType, $maxType] = count($type->genericTypes) === 1
            ? [null, $type->genericTypes[0]]
            : [$type->genericTypes[0], $type->genericTypes[1]];

        [$min, $max] = [$this->build($minType, $context), $this->build($maxType, $context)];

        return new StringType($min->getValue(), $max->getValue());
    }

    /**
     * @param GenericTypeNode $type
     * @param Context $context
     * @return ObjectType<mixed>
     * @throws ReflectionException
     */
    private function buildGenericObjectType(GenericTypeNode $type, Context $context): ObjectType
    {
        return $this->reflector->getReflection(
            $context->resolve($type->type->name),
            array_map(fn(TypeNode $genericType) => $this->build($genericType, $context), $type->genericTypes),
        );
    }
}

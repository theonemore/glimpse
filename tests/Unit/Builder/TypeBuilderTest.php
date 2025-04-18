<?php

use Fw2\Glimpse\Builder\TypeBuilder;
use Fw2\Glimpse\Context\Context;
use Fw2\Glimpse\Entity\PromiseObject;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\{NullType};
use Fw2\Glimpse\Types\ArrayType;
use Fw2\Glimpse\Types\BoolType;
use Fw2\Glimpse\Types\FloatType;
use Fw2\Glimpse\Types\IntType;
use Fw2\Glimpse\Types\ObjectType;
use Fw2\Glimpse\Types\OptionType;
use Fw2\Glimpse\Types\StringType;
use Fw2\Glimpse\Types\UnionType;
use phpDocumentor\Reflection\PseudoTypes\PositiveInteger;
use phpDocumentor\Reflection\Types\{Array_,
    Boolean,
    Float_,
    Integer,
    Null_,
    Nullable,
    Parent_,
    Self_,
    Static_,
    String_};
use phpDocumentor\Reflection\Types\AggregatedType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Namespace_;

beforeEach(function () {
    $reflectorMock = mock(Reflector::class);
    $reflectorMock->shouldReceive('reflect')
        ->with('TestClass', true)
        ->andReturn(new PromiseObject('TestClass', $reflectorMock));

    $reflectorMock->shouldReceive('reflect')
        ->with('Namespace\\SomeClass', true)
        ->andReturn(new ObjectType('Namespace\\SomeClass'));

    $reflectorMock->shouldReceive('reflect')
        ->with('Fully\\Qualified\\Class', true)
        ->andReturn(new ObjectType('Fully\\Qualified\\Class'));

    $this->builder = new TypeBuilder($reflectorMock);
    $this->ctx = new Context();
});

it('builds primitive types from Identifier', function () {
    expect($this->builder->build(new Identifier('string')))->toBeInstanceOf(StringType::class)
        ->and($this->builder->build(new Identifier('int')))->toBeInstanceOf(IntType::class)
        ->and($this->builder->build(new Identifier('float')))->toBeInstanceOf(FloatType::class)
        ->and($this->builder->build(new Identifier('null')))->toBeInstanceOf(NullType::class);
});

it('builds nullable types', function () {
    $nullableType = new NullableType(new Identifier('string'));
    /** @var OptionType $result */
    $result = $this->builder->build($nullableType, $this->ctx);

    expect($result)->toBeInstanceOf(OptionType::class)
        ->and($result->getOf())->toBeInstanceOf(StringType::class);
});

it('builds fully qualified names', function () {
    $result = $this->builder->build(new Name\FullyQualified('Fully\\Qualified\\Class'));

    expect($result)->toBeInstanceOf(ObjectType::class);
});

it('builds relative names with context', function () {
    $result = $this->builder->build(new Name('SomeClass'), new Context(new Namespace_(new Name('Namespace'))));
    expect($result)->toBeInstanceOf(ObjectType::class);
});

it('builds self type with context', function () {
    $result = $this->builder->build(new Identifier('self'), $this->ctx->for('TestClass'));
    expect($result)->toBeInstanceOf(PromiseObject::class);
});

it('builds static type with context', function () {
    $result = $this->builder->build(new Identifier('static'), $this->ctx->for('TestClass'));
    expect($result)->toBeInstanceOf(PromiseObject::class);
});

it('builds docblock types', function () {
    expect($this->builder->build(new String_()))->toBeInstanceOf(StringType::class)
        ->and($this->builder->build(new Float_()))->toBeInstanceOf(FloatType::class)
        ->and($this->builder->build(new Integer()))->toBeInstanceOf(IntType::class)
        ->and($this->builder->build(new Boolean()))->toBeInstanceOf(BoolType::class)
        ->and($this->builder->build(new Null_()))->toBeInstanceOf(NullType::class);
});

it('builds array types', function () {
    $arrayType = new Array_(new String_());
    /** @var ArrayType $result */
    $result = $this->builder->build($arrayType);

    expect($result)->toBeInstanceOf(ArrayType::class)
        ->and($result->getOf())->toBeInstanceOf(StringType::class);
});

it('builds union types', function () {
    $aggregatedType = mock(AggregatedType::class);
    $aggregatedType->shouldReceive('getIterator')
        ->andReturn(new ArrayIterator([new String_(), new Integer()]));

    /** @var UnionType $result */
    $result = $this->builder->build($aggregatedType);

    expect($result)->toBeInstanceOf(UnionType::class)
        ->and(iterator_to_array($result->getIterator()))->toHaveCount(2);
});

it('builds nullable docblock types', function () {
    $nullableType = new Nullable(new String_());
    /** @var OptionType $result */
    $result = $this->builder->build($nullableType, $this->ctx);

    expect($result)->toBeInstanceOf(OptionType::class)
        ->and($result->getOf())->toBeInstanceOf(StringType::class);
});

it('builds special integer types', function () {
    $positiveInt = new PositiveInteger();
    /** @var IntType $result */
    $result = $this->builder->build($positiveInt);

    expect($result)->toBeInstanceOf(IntType::class)
        ->and($result->getMin())->toBe(0);
});

it('builds special Static_ type', function () {
    $unsupportedType = new Static_();
    $result = $this->builder->build($unsupportedType, $this->ctx->for('TestClass'));
    expect($result)->toBeInstanceOf(PromiseObject::class)
        ->and($result->getFqcn())->toBe('TestClass');
});

it('builds special Self_ type', function () {
    $unsupportedType = new Self_();
    /** @var PromiseObject $result */
    $result = $this->builder->build($unsupportedType, $this->ctx->for('TestClass'));
    expect($result)->toBeInstanceOf(PromiseObject::class)
        ->and($result->getFqcn())->toBe('TestClass');
});


it('calls reflector reflect with parent class when Parent_ type is encountered', function () {
    $parentClass = 'SomeParentClass';

    $ctx = mock(Context::class);

    $ctx->shouldReceive('getParent')->andReturn($parentClass);

    $reflector = mock(Reflector::class);

    $builder = new TypeBuilder($reflector);

    $parentReflection = mock(ObjectType::class);

    $reflector->shouldReceive('reflect')->with($parentClass, true)->andReturn($parentReflection);

    $parentType = new Parent_();

    $result = $builder->build($parentType, $ctx);

    expect($result)->toBe($parentReflection);
    $reflector->shouldHaveReceived('reflect', [$parentClass, true]);
});

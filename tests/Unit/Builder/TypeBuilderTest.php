<?php

use Fw2\Glimpse\Builders\PhpTypeBuilder;
use Fw2\Glimpse\Context;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\{NullType, PromiseObject};
use Fw2\Glimpse\Types\BoolType;
use Fw2\Glimpse\Types\FloatType;
use Fw2\Glimpse\Types\IntType;
use Fw2\Glimpse\Types\ObjectType;
use Fw2\Glimpse\Types\OptionType;
use Fw2\Glimpse\Types\StringType;
use Fw2\Glimpse\Types\UnionType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;

beforeEach(function () {
    $reflectorMock = mock(Reflector::class);
    $reflectorMock->shouldReceive('getReflection')
        ->with('TestClass')
        ->andReturn(new ObjectType('TestClass'));

    $reflectorMock->shouldReceive('getReflection')
        ->with('Namespace\\SomeClass')
        ->andReturn(new ObjectType('Namespace\\SomeClass'));

    $reflectorMock->shouldReceive('getReflection')
        ->with('\\Fully\\Qualified\\Class')
        ->andReturn(new ObjectType('Fully\\Qualified\\Class'));

    $this->builder = new PhpTypeBuilder($reflectorMock);
    $this->ctx = new Context();
});

it('builds primitive types from Identifier', function () {
    expect($this->builder->build(new Identifier('string'), $this->ctx))->toBeInstanceOf(StringType::class)
        ->and($this->builder->build(new Identifier('int'), $this->ctx))->toBeInstanceOf(IntType::class)
        ->and($this->builder->build(new Identifier('float'), $this->ctx))->toBeInstanceOf(FloatType::class)
        ->and($this->builder->build(new Identifier('null'), $this->ctx))->toBeInstanceOf(NullType::class);
});

it('builds nullable types', function () {
    $nullableType = new NullableType(new Identifier('string'));
    /** @var OptionType $result */
    $result = $this->builder->build($nullableType, $this->ctx);

    expect($result)->toBeInstanceOf(OptionType::class)
        ->and($result->getOf())->toBeInstanceOf(StringType::class);
});

it('builds fully qualified names', function () {
    $result = $this->builder->build(new Name\FullyQualified('Fully\\Qualified\\Class'), $this->ctx);

    expect($result)->toBeInstanceOf(ObjectType::class);
});

it('builds relative names with context', function () {
    $result = $this->builder->build(
        new Name('SomeClass'),
        new Context('Namespace'),
    );
    expect($result)->toBeInstanceOf(ObjectType::class);
});

it('builds self type with context', function () {
    $result = $this->builder->build(new Identifier('self'), $this->ctx->copy()->setStatic('TestClass'));
    expect($result)->toBeInstanceOf(ObjectType::class);
});

it('builds static type with context', function () {
    $result = $this->builder->build(new Identifier('static'), $this->ctx->copy()->setStatic('TestClass'));
    expect($result)->toBeInstanceOf(ObjectType::class);
});

it('builds docblock types', function () {
    expect($this->builder->build(new Identifier('string'), $this->ctx))->toBeInstanceOf(StringType::class)
        ->and($this->builder->build(new Identifier('float'), $this->ctx))->toBeInstanceOf(FloatType::class)
        ->and($this->builder->build(new Identifier('int'), $this->ctx))->toBeInstanceOf(IntType::class)
        ->and($this->builder->build(new Identifier('bool'), $this->ctx))->toBeInstanceOf(BoolType::class)
        ->and($this->builder->build(new Identifier('null'), $this->ctx))->toBeInstanceOf(NullType::class);
});



it('builds union types', function () {
    $aggregatedType = new \PhpParser\Node\UnionType([
        new Name\FullyQualified("Fully\\Qualified\\Class"),
        new Name('TestClass'),
    ]);;

    /** @var UnionType $result */
    $result = $this->builder->build($aggregatedType, $this->ctx);

    expect($result)->toBeInstanceOf(UnionType::class)
        ->and(iterator_to_array($result->getIterator()))->toHaveCount(2);
});

it('builds nullable docblock types', function () {
    $nullableType = new NullableType(new Identifier('string'));
    /** @var OptionType $result */
    $result = $this->builder->build($nullableType, $this->ctx);

    expect($result)->toBeInstanceOf(OptionType::class)
        ->and($result->getOf())->toBeInstanceOf(StringType::class);
});

it('builds special static type', function () {
    $unsupportedType = new Identifier('static');
    $result = $this->builder->build($unsupportedType, $this->ctx->copy()->setStatic('TestClass'));
    expect($result)->toBeInstanceOf(ObjectType::class)
        ->and($result->getFqcn())->toBe('TestClass');
});

it('builds special self type', function () {
    $unsupportedType = new Identifier('self');
    /** @var PromiseObject $result */
    $result = $this->builder->build($unsupportedType, $this->ctx->copy()->setStatic('TestClass'));
    expect($result)->toBeInstanceOf(ObjectType::class)
        ->and($result->getFqcn())->toBe('TestClass');
});


it('calls reflector reflect with parent class when Parent_ type is encountered', function () {
    $parentClass = 'SomeParentClass';

    $ctx = mock(Context::class);

    $ctx->shouldReceive('getParent')->andReturn($parentClass);

    $reflector = mock(Reflector::class);

    $builder = new PhpTypeBuilder($reflector);

    $parentReflection = mock(ObjectType::class);

    $reflector->shouldReceive('getReflection')->with($parentClass)->andReturn($parentReflection);

    $parentType = new Identifier('parent');

    $result = $builder->build($parentType, $ctx);

    expect($result)->toBe($parentReflection);
    $reflector->shouldHaveReceived('getReflection', [$parentClass]);
});

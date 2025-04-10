<?php

use Fw2\Mentalist\Builder\AttributeBuilder;
use Fw2\Mentalist\Builder\Context;
use Fw2\Mentalist\Builder\DocBlockHelper;
use Fw2\Mentalist\Builder\ObjectProperty;
use Fw2\Mentalist\Builder\PropertyBuilder;
use Fw2\Mentalist\Builder\TypeBuilder;
use Fw2\Mentalist\Types\BoolType;
use Fw2\Mentalist\Types\FloatType;
use Fw2\Mentalist\Types\IntType;
use Fw2\Mentalist\Types\NullType;
use Fw2\Mentalist\Types\StringType;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Types\String_;
use PhpParser\Comment\Doc;
use PhpParser\Modifiers;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

beforeEach(function () {
    $this->ctx = new Context();
    $this->typeBuilder = mock(TypeBuilder::class);
    $this->typeBuilder->shouldReceive('build')
        ->with(null, $this->ctx)
        ->andReturn(new NullType());

    $this->attributeBuilder = mock(AttributeBuilder::class);

    $this->docBlockHelper = mock(DocBlockHelper::class);
    $this->docBlockHelper->shouldReceive('create')
        ->with(null, $this->ctx)
        ->andReturnNull();
    $this->docBlockHelper->shouldReceive('getVarType')
        ->with(null)
        ->andReturnNull();

    $this->propertyBuilder = new PropertyBuilder(
        $this->typeBuilder,
        $this->attributeBuilder,
        $this->docBlockHelper
    );
});

it('builds property without docblock', function () {
    $propertyNode = new Property(
        Modifiers::PUBLIC,
        [new PropertyProperty('testProperty')],
        [],
        new Identifier('string')
    );

    $this->docBlockHelper->shouldReceive('create')->with(null, $this->ctx)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->with(null)->andReturn(null);

    $this->typeBuilder->shouldReceive('build')
        ->with(Mockery::on(fn($arg) => $arg instanceof Identifier), $this->ctx)
        ->andReturn(new StringType());

    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);

    $properties = $this->propertyBuilder->build($propertyNode, $this->ctx);

    expect($properties)->toHaveCount(1)
        ->and($properties[0])->toBeInstanceOf(ObjectProperty::class)
        ->and($properties[0]->name)->toBe('testProperty')
        ->and($properties[0]->type)->toBeInstanceOf(StringType::class);
});

it('builds property with docblock type', function () {
    $docBlock = new DocBlock();

    $propertyNode = new Property(
        Modifiers::PUBLIC,
        [new PropertyProperty('docProperty')],
        ['comments' => [new Doc('/** @var string */')]]
    );

    $this->docBlockHelper->shouldReceive('create')->with('/** @var string */', $this->ctx)->andReturn($docBlock);
    $this->docBlockHelper->shouldReceive('getVarType')->with($docBlock)->andReturn(new String_());

    $this->typeBuilder->shouldReceive('build')
        ->with(Mockery::on(fn($arg) => $arg instanceof String_), $this->ctx)
        ->andReturn(new StringType());

    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);

    $properties = $this->propertyBuilder->build($propertyNode, $this->ctx);

    expect($properties[0]->type)->toBeInstanceOf(StringType::class);
});

it('builds property with attributes', function () {
    $attrGroup = new AttributeGroup([
        new Attribute(new Name('TestAttribute'))
    ]);

    $propertyNode = new Property(
        Modifiers::PUBLIC,
        [new PropertyProperty('attrProperty')],
        [],
        new Identifier('int'),
        [$attrGroup]
    );

    $this->docBlockHelper->shouldReceive('create')->with(null, $this->ctx)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->with(null)->andReturn(null);

    $this->typeBuilder->shouldReceive('build')
        ->with(Mockery::on(fn($arg) => $arg instanceof Identifier), $this->ctx)
        ->andReturn(new IntType());

    $attributeResult = ['TestAttribute' => [new \Fw2\Mentalist\Builder\Attribute('TestAttribute', [])]];
    $this->attributeBuilder->shouldReceive('build')->with([$attrGroup], $this->ctx)->andReturn($attributeResult);

    $properties = $this->propertyBuilder->build($propertyNode, $this->ctx);

    expect($properties[0]->attributes)->toBe($attributeResult);
});

it('builds multiple properties in one declaration', function () {
    $propertyNode = new Property(
        Modifiers::PUBLIC,
        [
            new PropertyProperty('prop1'),
            new PropertyProperty('prop2'),
        ],
        [],
        new Identifier('float')
    );

    $this->docBlockHelper->shouldReceive('create')->with(null, $this->ctx)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->with(null)->andReturn(null);

    $this->typeBuilder->shouldReceive('build')
        ->with(Mockery::on(fn($arg) => $arg instanceof Identifier), $this->ctx)
        ->andReturn(new FloatType());

    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);

    $properties = $this->propertyBuilder->build($propertyNode, $this->ctx);

    expect($properties)->toHaveCount(2)
        ->and($properties[0]->name)->toBe('prop1')
        ->and($properties[1]->name)->toBe('prop2');
});

it('builds property with different modifiers', function () {
    $propertyNode = new Property(
        Modifiers::PROTECTED | Modifiers::READONLY,
        [new PropertyProperty('modifierProperty')],
        [],
        new Identifier('bool')
    );

    $this->docBlockHelper->shouldReceive('create')->with(null, $this->ctx)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->with(null)->andReturn(null);

    $this->typeBuilder->shouldReceive('build')
        ->with(Mockery::on(fn($arg) => $arg instanceof Identifier), $this->ctx)
        ->andReturn(new BoolType());

    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);

    $properties = $this->propertyBuilder->build($propertyNode, $this->ctx);

    expect($properties)->toHaveCount(1)
        ->and($properties[0]->name)->toBe('modifierProperty');
});

it('throws ReflectionException when type building fails', function () {
    $propertyNode = new Property(
        Modifiers::PUBLIC,
        [new PropertyProperty('failingProp')],
        [],
        new Identifier('invalid')
    );

    $this->docBlockHelper->shouldReceive('create')->with(null, $this->ctx)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->with(null)->andReturn(null);

    $this->typeBuilder->shouldReceive('build')->andThrow(new ReflectionException('Type building failed'));

    $this->propertyBuilder->build($propertyNode, $this->ctx);
})->throws(ReflectionException::class);

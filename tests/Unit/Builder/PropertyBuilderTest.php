<?php


use Fw2\Glimpse\Builders\AttributeBuilder;
use Fw2\Glimpse\Builders\DocTypeBuilder;
use Fw2\Glimpse\Builders\PhpTypeBuilder;
use Fw2\Glimpse\Builders\PropertyBuilder;
use Fw2\Glimpse\Context;
use Fw2\Glimpse\PhpDoc\DocBlockHelper;
use Fw2\Glimpse\Types\BoolType;
use Fw2\Glimpse\Types\Entity\ObjectProperty;
use Fw2\Glimpse\Types\FloatType;
use Fw2\Glimpse\Types\IntType;
use Fw2\Glimpse\Types\NullType;
use Fw2\Glimpse\Types\StringType;
use PhpParser\Comment\Doc;
use PhpParser\Modifiers;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

beforeEach(function () {
    $this->ctx = (new Context())->setStatic('TestClass');
    $this->docTypeBuilder = mock(DocTypeBuilder::class);
    $this->phpTypeBuilder = mock(PhpTypeBuilder::class);
    $this->phpTypeBuilder->shouldReceive('build')
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
        $this->docTypeBuilder,
        $this->phpTypeBuilder,
        $this->attributeBuilder,
        $this->docBlockHelper
    );
});

it('builds property without docblock', function () {
    $propertyNode = new Property(
        Modifiers::PUBLIC,
        [new PropertyProperty('testProperty')],
        [],
        $identifier = new Identifier('string')
    );

    $this->docBlockHelper->shouldReceive('create')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->with(null)->andReturn(null);
    $this->docTypeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);

    $this->phpTypeBuilder->shouldReceive('build')
        ->with($identifier, $this->ctx)
        ->andReturn(new StringType());

    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);

    $properties = $this->propertyBuilder->build($propertyNode, $this->ctx);

    expect($properties)->toHaveCount(1)
        ->and($properties[0])->toBeInstanceOf(ObjectProperty::class)
        ->and($properties[0]->name)->toBe('testProperty')
        ->and($properties[0]->type)->toBeInstanceOf(StringType::class);
});

it('builds property with docblock type', function () {
    $docBlock = new PhpDocNode([new PhpDocTextNode($docText = '/** @var string */')]);

    $propertyNode = new Property(
        Modifiers::PUBLIC,
        [new PropertyProperty('docProperty')],
        ['comments' => [new Doc($docText)]]
    );

    $this->docBlockHelper->shouldReceive('create')->with('/** @var string */')->andReturn($docBlock);
    $this->docBlockHelper->shouldReceive('getVarType')->with($docBlock)->andReturn($stringType = new IdentifierTypeNode('string'));

    $this->docTypeBuilder->shouldReceive('build')->with($stringType , $this->ctx)->andReturn(new StringType());

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

    $this->docBlockHelper->shouldReceive('create')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->with(null)->andReturn(null);

    $this->docTypeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);

    $this->phpTypeBuilder->shouldReceive('build')
        ->with(Mockery::on(fn($arg) => $arg instanceof Identifier), $this->ctx)
        ->andReturn(new IntType());

    $attributeResult = [new \Fw2\Glimpse\Types\Entity\Attribute('TestAttribute', [])];
    $this->attributeBuilder->shouldReceive('build')->with([$attrGroup], $this->ctx)->andReturn($attributeResult);

    $properties = $this->propertyBuilder->build($propertyNode, $this->ctx);

    expect($properties[0]->getAttributes())->toBe($attributeResult);
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

    $this->docBlockHelper->shouldReceive('create')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->with(null)->andReturn(null);
    $this->docTypeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);

    $this->phpTypeBuilder->shouldReceive('build')
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

    $this->docBlockHelper->shouldReceive('create')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->with(null)->andReturn(null);
    $this->docTypeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);

    $this->phpTypeBuilder->shouldReceive('build')
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

    $this->docBlockHelper->shouldReceive('create')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->with(null)->andReturn(null);

    $this->docTypeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);
    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);
    $this->phpTypeBuilder->shouldReceive('build')->andThrow(new ReflectionException('Type building failed'));

    $this->propertyBuilder->build($propertyNode, $this->ctx);
})->throws(ReflectionException::class, 'Type building failed');

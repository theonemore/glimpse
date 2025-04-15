<?php

use Fw2\Glimpse\Builder\AttributeBuilder;
use Fw2\Glimpse\Builder\ClassBuilder;
use Fw2\Glimpse\Builder\MethodBuilder;
use Fw2\Glimpse\Builder\PropertyBuilder;
use Fw2\Glimpse\Context\Context;
use Fw2\Glimpse\Entity;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\ObjectType;
use PhpParser\Modifiers;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;

beforeEach(function () {
    $this->attributeBuilder = mock(AttributeBuilder::class);
    $this->methodBuilder = mock(MethodBuilder::class);
    $this->propertyBuilder = mock(PropertyBuilder::class);
    $this->reflector = mock(Reflector::class);

    $this->builder = new ClassBuilder(
        $this->attributeBuilder,
        $this->methodBuilder,
        $this->propertyBuilder,
        $this->reflector
    );

    $this->ctx = (new Context())->setStatic('TestClass');
});

afterEach(function () {
    Mockery::close();
});

it('builds basic class with name', function () {
    $classNode = new Class_('TestClass');
    $classNode->name = new Identifier('TestClass');

    $this->attributeBuilder->shouldReceive('build')
        ->with([], $this->ctx)
        ->andReturn([]);

    $result = $this->builder->build($classNode, $this->ctx);

    expect($result)->toBeInstanceOf(ObjectType::class)
        ->and($result->getFqcn())->toBe('TestClass');
});

it('builds class with attributes', closure: function () {
    $classNode = new Class_('TestClass');
    $attrGroup = new \PhpParser\Node\AttributeGroup([
        new \PhpParser\Node\Attribute(new Name('TestAttribute'))
    ]);
    $classNode->attrGroups = [$attrGroup];

    $this->attributeBuilder->shouldReceive('build')
        ->with([$attrGroup], $this->ctx)
        ->andReturn([new Entity\Attribute('TestAttribute', [])]);

    $result = $this->builder->build($classNode, $this->ctx);

    expect($result->getAttributes())->toHaveCount(1);
});

it('builds class with methods', function () {
    $classNode = new Class_('TestClass');
    $methodNode = new ClassMethod('testMethod');
    $methodNode->flags = Modifiers::PUBLIC;
    $classNode->stmts = [$methodNode];

    $this->attributeBuilder->shouldReceive('build')->andReturn([]);

    $methodObject = new Entity\ObjectMethod('testMethod', 'TestClass');
    $this->methodBuilder->shouldReceive('build')
        ->with($methodNode, $this->ctx)
        ->andReturn($methodObject);

    $result = $this->builder->build($classNode, $this->ctx);

    expect($result->getMethods())->toHaveCount(1)
        ->and($result->getMethods()['testMethod']->getName())->toBe('testMethod');
});



it('skips non-public methods', function () {
    $classNode = new Class_('TestClass');

    $publicMethodNode = new ClassMethod('publicMethod');
    $publicMethodNode->flags = Modifiers::PUBLIC;

    $nonPublicMethodNode = new ClassMethod('nonPublicMethod');
    $nonPublicMethodNode->flags = Modifiers::PRIVATE;
    $classNode->stmts = [$publicMethodNode, $nonPublicMethodNode];

    $this->attributeBuilder->shouldReceive('build')->andReturn([]);

    $publicMethodObject = new Entity\ObjectMethod('publicMethod', 'TestClass');
    $this->methodBuilder->shouldReceive('build')
        ->with($publicMethodNode, $this->ctx)
        ->andReturn($publicMethodObject);

    $result = $this->builder->build($classNode, $this->ctx);

    expect($result->getMethods())->toHaveCount(1)
        ->and($result->getMethods()['publicMethod']->getName())->toBe('publicMethod')
        ->and($result->getMethods())->not()->toHaveKey('nonPublicMethod');
});


it('builds class with properties', function () {
    $classNode = new Class_('TestClass');
    $propertyNode = new Property(Modifiers::PUBLIC, [new PropertyProperty('testProperty')]);
    $classNode->stmts = [$propertyNode];

    $this->attributeBuilder->shouldReceive('build')->andReturn([]);

    $propertyObject = new Entity\ObjectProperty('testProperty', null, 'TestClass');
    $this->propertyBuilder->shouldReceive('build')
        ->with($propertyNode, $this->ctx)
        ->andReturn([$propertyObject]);

    $result = $this->builder->build($classNode, $this->ctx);

    expect($result->getProperties())->toHaveCount(1)
        ->and($result->getProperties()['testProperty']->getName())->toBe('testProperty');
});

it('builds class with parent', function () {
    $classNode = new Class_('TestClass');
    $classNode->extends = new Name('ParentClass');

    $this->attributeBuilder->shouldReceive('build')->andReturn([]);

    $parentObject = new ObjectType('ParentClass');
    $parentObject->addProperty(new Entity\ObjectProperty('parentProperty', null, 'TestClass'));
    $parentObject->addMethod(new Entity\ObjectMethod('parentMethod', 'TestClass'));
    $this->reflector->shouldReceive('reflect')
        ->with('ParentClass')
        ->andReturn($parentObject);

    $result = $this->builder->build($classNode, $this->ctx);

    expect($result->getMethod('parentMethod')->getName())->toBe('parentMethod')
        ->and($result->getProperty('parentProperty')->getName())->toBe('parentProperty');;
});

it('builds interface with extends', function () {
    $interfaceNode = new Interface_('TestInterface');
    $interfaceNode->extends = [new Name('ParentInterface')];

    $this->attributeBuilder->shouldReceive('build')->andReturn([]);

    $parentObject = new ObjectType('ParentInterface');
    $parentObject->addMethod(new Entity\ObjectMethod('parentMethod', ''));
    $this->reflector->shouldReceive('reflect')
        ->with('ParentInterface')
        ->andReturn($parentObject);

    $result = $this->builder->build($interfaceNode, $this->ctx);

    expect($result->getMethod('parentMethod')->getName())->toContain('parentMethod');
});

it('builds class with traits', function () {
    $classNode = new Class_('TestClass');
    $traitNode = new TraitUse([new Name('TestTrait')]);
    $classNode->stmts = [$traitNode];

    $this->attributeBuilder->shouldReceive('build')->andReturn([]);

    $traitObject = new ObjectType('TestTrait');
    $traitObject
        ->addMethod(new Entity\ObjectMethod('traitMethod', 'TestClass'))
        ->addProperty(new Entity\ObjectProperty('traitProperty', null, 'TestClass'));

    $this->reflector->shouldReceive('reflect')
        ->with('TestTrait')
        ->andReturn($traitObject);

    $result = $this->builder->build($classNode, $this->ctx);

    expect($result->getMethod('traitMethod')->getName())->toContain('traitMethod')
        ->and($result->getProperty('traitProperty')->getName())->toContain('traitProperty');
});

it('builds class with trait adaptations', function () {
    $classNode = new Class_('TestClass');
    $alias = new Alias(new Name('TestTrait'), 'originalMethod', Modifiers::PUBLIC, 'newMethod');
    $traitNode = new TraitUse([new Name('TestTrait')], [$alias]);
    $classNode->stmts = [$traitNode];

    $this->attributeBuilder->shouldReceive('build')->andReturn([]);

    $traitObject = new ObjectType('TestTrait');
    $traitObject->addMethod(new Entity\ObjectMethod('originalMethod', 'TestClass'));
    $this->reflector->shouldReceive('reflect')
        ->with('TestTrait')
        ->andReturn($traitObject);

    $result = $this->builder->build($classNode, $this->ctx);

    expect($result->getMethods())->toHaveCount(1)
        ->and($result->getMethods()['newMethod']->getName())->toBe('newMethod');
});

it('merges from trait correctly', function () {
    $target = new ObjectType('Target');
    $source = new ObjectType('Source');

    $source->addProperty(new Entity\ObjectProperty('prop', null, 'TestClass'));
    $source->addMethod(new Entity\ObjectMethod('method', 'TestClass'));
    $source->addAttribute(new Entity\Attribute('attr', []));

    $this->builder->mergeFromTrait($target, $source);

    expect($target->getProperties())->toHaveCount(1)
        ->and($target->getMethods())->toHaveCount(1)
        ->and($target->getAttributes())->toHaveCount(1);
});

it('merges from parent correctly', function () {
    $target = new ObjectType('Child');
    $source = new ObjectType('Parent');

    $source->addProperty(new Entity\ObjectProperty('prop', null, 'TestClass'));
    $source->addMethod(new Entity\ObjectMethod('method', 'TestClass'));

    $this->builder->mergeFromParent($target, $source);

    expect($target->getProperties())->toHaveCount(1)
        ->and($target->getMethods())->toHaveCount(1);
});
<?php

use Fw2\Mentalist\Builder\AttributeBuilder;
use Fw2\Mentalist\Builder\Context;
use Fw2\Mentalist\Builder\DocBlockHelper;
use Fw2\Mentalist\Builder\MethodBuilder;
use Fw2\Mentalist\Builder\ObjectMethod;
use Fw2\Mentalist\Builder\TypeBuilder;
use Fw2\Mentalist\Types\IntType;
use Fw2\Mentalist\Types\StringType;
use phpDocumentor\Reflection\Types\String_;
use PhpParser\Comment\Doc;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;

beforeEach(function () {
    $this->typeBuilder = mock(TypeBuilder::class);
    $this->attributeBuilder = mock(AttributeBuilder::class);
    $this->docBlockHelper = mock(DocBlockHelper::class);

    $this->builder = new MethodBuilder(
        $this->typeBuilder,
        $this->attributeBuilder,
        $this->docBlockHelper
    );

    $this->ctx = new Context();
});

it('builds basic method without parameters', function () {
    $methodNode = new ClassMethod('testMethod');

    $this->docBlockHelper->shouldReceive('create')->with(null, $this->ctx)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getSummary')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getDescription')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getReturnType')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getParamTypes')->andReturn([]);

    $this->typeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);

    $method = $this->builder->build($methodNode, $this->ctx);

    expect($method)->toBeInstanceOf(ObjectMethod::class);
    expect($method->getName())->toBe('testMethod');
    expect($method->getParameters())->toBeEmpty();
    expect($method->getReturnType())->toBeNull();
});

it('builds method with return type', function () {
    $methodNode = new ClassMethod('testMethod', [
        'returnType' => new Identifier('string')
    ]);

    $docBlock = new phpDocumentor\Reflection\DocBlock;

    $this->docBlockHelper->shouldReceive('create')->with(null, $this->ctx)->andReturn($docBlock);
    $this->docBlockHelper->shouldReceive('getReturnType')->with($docBlock)->andReturn(new String_());

    $this->typeBuilder->shouldReceive('build')
        ->with(Mockery::on(fn ($arg) => $arg instanceof String_), $this->ctx)
        ->andReturn(new StringType());

    $this->docBlockHelper->shouldReceive('getSummary')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getDescription')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getParamTypes')->andReturn([]);

    $method = $this->builder->build($methodNode, $this->ctx);

    expect($method->getReturnType())->toBeInstanceOf(StringType::class);
});

it('builds method with parameters', function () {
    $param1 = new Param(new Variable('param1'), type: new Identifier('int'));
    $param2 = new Param(new Variable('param2'));

    $methodNode = new ClassMethod('testMethod', [
        'params' => [$param1, $param2]
    ]);

    $this->docBlockHelper->shouldReceive('create')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getSummary')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getDescription')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getReturnType')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getParamTypes')->with(null)->andReturn([]);

    $this->typeBuilder->shouldReceive('build')
        ->with(Mockery::on(fn ($arg) => $arg instanceof Identifier), $this->ctx)
        ->andReturn(new IntType());

    $this->typeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);

    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);

    $method = $this->builder->build($methodNode, $this->ctx);

    expect($method->getParameters())->toHaveCount(2)
        ->and($method->getParameters()['param1']->getName())->toBe('param1')
        ->and($method->getParameters()['param1']->getType())->toBeInstanceOf(IntType::class)
        ->and($method->getParameters()['param2']->getType())->toBeNull();
});

it('builds method with parameter attributes', function () {
    $param = new Param(
        new Variable('param'),
        type: null,
        attrGroups: $attrGroups = [new AttributeGroup([new PhpParser\Node\Attribute(new Name('TestAttribute'))])]
    );

    $methodNode = new ClassMethod('testMethod', [
        'params' => [$param]
    ]);

    $this->docBlockHelper->shouldReceive('create')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getSummary')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getDescription')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getReturnType')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getVarType')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getParamTypes')->andReturn([]);

    $this->typeBuilder->shouldReceive('build')->andReturn(null);

    $this->attributeBuilder->shouldReceive('build')
        ->with($attrGroups, $this->ctx)
        ->andReturn([new Fw2\Mentalist\Builder\Attribute('TestAttribute', [])]);

    $method = $this->builder->build($methodNode, $this->ctx);

    expect($method->getParameters()['param']->getAttributes())->toHaveCount(1);
});

it('builds method with docblock metadata', function () {
    $methodNode = new ClassMethod('testMethod', [], [
        'comments' => [$doc = new Doc('/** Test summary. Test description. */')]
    ]);

    $docBlock = new phpDocumentor\Reflection\DocBlock;

    $this->docBlockHelper->shouldReceive('create')->with($doc->getText(), $this->ctx)->andReturn($docBlock);
    $this->docBlockHelper->shouldReceive('getSummary')->with($docBlock)->andReturn('Test summary');
    $this->docBlockHelper->shouldReceive('getDescription')->with($docBlock)->andReturn('Test description');
    $this->docBlockHelper->shouldReceive('getReturnType')->with($docBlock)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getParamTypes')->with($docBlock)->andReturn([]);

    $this->typeBuilder->shouldReceive('build')->andReturn(null);

    $method = $this->builder->build($methodNode, $this->ctx);

    expect($method->getSummary())->toBe('Test summary')
        ->and($method->getDescription())->toBe('Test description');
});

it('throws ReflectionException when type building fails', function () {
    $methodNode = new ClassMethod('testMethod', [
        'returnType' => new Identifier('invalid')
    ]);

    $this->docBlockHelper->shouldReceive('create')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getSummary')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getDescription')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getReturnType')->andReturn(null);

    $this->typeBuilder->shouldReceive('build')
        ->andThrow(new ReflectionException('Type building failed'));

    $this->builder->build($methodNode, $this->ctx);
})->throws(ReflectionException::class);

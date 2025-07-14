<?php


use Fw2\Glimpse\Builders\AttributeBuilder;
use Fw2\Glimpse\Builders\DocTypeBuilder;
use Fw2\Glimpse\Builders\MethodBuilder;
use Fw2\Glimpse\Builders\PhpTypeBuilder;
use Fw2\Glimpse\Context;
use Fw2\Glimpse\PhpDoc\DocBlockHelper;
use Fw2\Glimpse\Types\Entity\Attribute;
use Fw2\Glimpse\Types\Entity\ObjectMethod;
use Fw2\Glimpse\Types\IntType;
use Fw2\Glimpse\Types\StringType;
use phpDocumentor\Reflection\Types\String_;
use PhpParser\Comment\Doc;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

beforeEach(function () {
    $this->docTypeBuilder = mock(DocTypeBuilder::class);
    $this->phpTypeBuilder = mock(PhpTypeBuilder::class);
    $this->attributeBuilder = mock(AttributeBuilder::class);
    $this->docBlockHelper = mock(DocBlockHelper::class);

    $this->builder = new MethodBuilder(
        $this->docTypeBuilder,
        $this->phpTypeBuilder,
        $this->attributeBuilder,
        $this->docBlockHelper
    );

    $this->ctx = (new Context())->setStatic('TestClass');
});

it('builds basic method without parameters', function () {
    $methodNode = new ClassMethod('testMethod');

    $this->docBlockHelper->shouldReceive('create')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getSummary')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getDescription')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getReturnType')->with(null)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getParamTypes')->andReturn([]);
    $this->docBlockHelper->shouldReceive('getParamDescriptions')->andReturn([]);
    $this->phpTypeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);
    $this->docTypeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);
    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);

    $method = $this->builder->build($methodNode, $this->ctx);

    expect($method)->toBeInstanceOf(ObjectMethod::class)
        ->and($method->getName())->toBe('testMethod')
        ->and($method->getParameters())->toBeEmpty()
        ->and($method->getReturnType())->toBeNull();
});

it('builds method with return type', function () {
    $methodNode = new ClassMethod('testMethod', [
        'returnType' => new Identifier('string')
    ]);

    $docBlock = new PhpDocNode([]);
    $returnType = new IdentifierTypeNode('string');

    $this->docBlockHelper->shouldReceive('create')
        ->with(null)
        ->andReturn($docBlock);

    $this->docBlockHelper->shouldReceive('getReturnType')
        ->with($docBlock)
        ->andReturn($returnType);

    $this->phpTypeBuilder->shouldReceive('build')
        ->with(Mockery::on(fn($arg) => $arg instanceof String_), $this->ctx)
        ->andReturn(new StringType());

    $this->docBlockHelper->shouldReceive('getSummary')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getDescription')->andReturn(null);
    $this->docBlockHelper->shouldReceive('getParamTypes')->andReturn([]);

    $this->docBlockHelper->shouldReceive('getParamDescriptions')->andReturn([]);
    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);
    $this->docTypeBuilder->shouldReceive('build')->with($returnType, $this->ctx)->andReturn(new StringType());

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
    $this->docBlockHelper->shouldReceive('getParamDescriptions')->andReturn([]);
    $this->docBlockHelper->shouldReceive('getVarDescription')->with(null)->andReturn(null);

    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);

    $this->phpTypeBuilder->shouldReceive('build')
        ->with(Mockery::on(fn($arg) => $arg instanceof Identifier), $this->ctx)
        ->andReturn(new IntType());

    $this->phpTypeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);

    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);
    $this->docTypeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);

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
    $this->docBlockHelper->shouldReceive('getVarDescription')->with(null)->andReturn(null);

    $this->docBlockHelper->shouldReceive('getParamDescriptions')->andReturn([]);

    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);

    $this->phpTypeBuilder->shouldReceive('build')->andReturn(null);
    $this->docTypeBuilder->shouldReceive('build')->andReturn(null);

    $this->attributeBuilder->shouldReceive('build')
        ->with($attrGroups, $this->ctx)
        ->andReturn([new Attribute('TestAttribute', [])]);

    $method = $this->builder->build($methodNode, $this->ctx);

    expect($method->getParameters()['param']->getAttributes())->toHaveCount(1);
});

it('builds method with docblock metadata', function () {
    $methodNode = new ClassMethod('testMethod', [], [
        'comments' => [$doc = new Doc('/** Test summary. Test description. */')]
    ]);

    $docNode = new PhpDocNode([new PhpDocTextNode($methodNode->getDocComment()->getText())]);

    $this->docBlockHelper->shouldReceive('create')->with($doc->getText())->andReturn($docNode);
    $this->docBlockHelper->shouldReceive('getSummary')->with($docNode)->andReturn('Test summary');
    $this->docBlockHelper->shouldReceive('getDescription')->with($docNode)->andReturn('Test description');
    $this->docBlockHelper->shouldReceive('getReturnType')->with($docNode)->andReturn(null);
    $this->docBlockHelper->shouldReceive('getParamTypes')->with($docNode)->andReturn([]);

    $this->docBlockHelper->shouldReceive('getParamDescriptions')->andReturn([]);

    $this->docTypeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);

    $this->attributeBuilder->shouldReceive('build')->with([], $this->ctx)->andReturn([]);

    $this->phpTypeBuilder->shouldReceive('build')->andReturn(null);

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
    $this->docTypeBuilder->shouldReceive('build')->with(null, $this->ctx)->andReturn(null);

    $this->phpTypeBuilder->shouldReceive('build')
        ->andThrow(new ReflectionException('Type building failed'));

    $this->builder->build($methodNode, $this->ctx);
})->throws(ReflectionException::class);

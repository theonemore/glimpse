<?php

use Fw2\Glimpse\Ast\AstResolver;
use Fw2\Glimpse\Builder\ClassBuilder;
use Fw2\Glimpse\Context\Context;
use Fw2\Glimpse\Entity\PromiseObject;
use Fw2\Glimpse\Providers\ClassBuilderProvider;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\ObjectType;
use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\ParserFactory;

beforeEach(function () {
    $this->fqcn = 'App\\Example\\MyClass';

    $this->mockResolver = mock(AstResolver::class);
    $this->mockBuilder = mock(ClassBuilder::class);
    $this->mockClassBuilderProvider = mock(ClassBuilderProvider::class);
    $this->mockClassBuilderProvider
        ->shouldReceive('get')
        ->andReturn($this->mockBuilder);

    $this->reflector = new Reflector($this->mockResolver, $this->mockClassBuilderProvider);
});

it('returns ObjectPromise when ref is true', function () {
    $promise = $this->reflector->reflect($this->fqcn, true);

    expect($promise)->toBeInstanceOf(PromiseObject::class)
        ->and($promise->getFqcn())->toBe($this->fqcn);
});

it('builds and caches ObjectType from class AST', function () {
    $classNode = new Class_('MyClass');
    $classNode->namespacedName = new Name($classNode->name->name);

    $objectType = mock(ObjectType::class);
    $objectType->shouldReceive('getFqcn')->andReturn($this->fqcn);

    $this->mockResolver
        ->shouldReceive('resolve')
        ->with($this->fqcn)
        ->andReturn([$classNode]);

    $this->mockBuilder
        ->shouldReceive('build')
        ->with($classNode, \Mockery::type(Context::class))
        ->andReturn($objectType);

    $result = $this->reflector->reflect($this->fqcn);
    expect($result)->toBe($objectType);

    $cached = $this->reflector->reflect($this->fqcn);
    expect($cached)->toBe($result);
});

it('builds from Namespace_ statement', function () {
    $classNode = new Class_('MyClass');
    $namespaceNode = new Namespace_(new PhpParser\Node\Name('App\\Example'), [$classNode]);
    $objectType = mock(ObjectType::class);
    $objectType->shouldReceive('getFqcn')->andReturn($this->fqcn);

    $this->mockResolver
        ->shouldReceive('resolve')
        ->with($this->fqcn)
        ->andReturn([$namespaceNode]);

    $this->mockBuilder
        ->shouldReceive('build')
        ->andReturn($objectType);

    $result = $this->reflector->reflect($this->fqcn);

    expect($result)->toBe($objectType);
});

it('creates an instance of Reflector', function () {
    $reflector = Reflector::createInstance(new ParserFactory(), DocBlockFactory::createInstance());
    expect($reflector)->toBeInstanceOf(Reflector::class);
});

<?php

use Fw2\Glimpse\Ast\AstResolver;
use Fw2\Glimpse\PhpDoc\DocBlockHelper;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\ObjectType;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Parser;

beforeEach(function () {
    $this->fqcn = 'App\\Example\\MyClass';

    $this->mockResolver = mock(AstResolver::class);
    $this->parser = mock(Parser::class);
    $this->docblockHelper = mock(DocBlockHelper::class);
    $this->reflector = new Reflector($this->mockResolver, $this->parser, $this->docblockHelper);
});

it('returns ObjectType', function () {
    $cs = new Class_('MyClass');
    $ns = new Namespace_(new Name('App\\Example'), [$cs]);

    $this->mockResolver->shouldReceive('resolve')->with($this->fqcn)->andReturn([$ns]);
    $this->docblockHelper->shouldReceive('create')->with(null)->andReturn(null);

    $promise = $this->reflector->getReflection($this->fqcn);

    expect($promise)->toBeInstanceOf(ObjectType::class)
        ->and($promise->getFqcn())->toBe($this->fqcn);
});

it('builds and caches ObjectType from class AST', function () {
    $cs = new Class_('MyClass');
    $cs->namespacedName = new Name($cs->name->name);

    $ns = new Namespace_(new Name('App\\Example'), [$cs]);

    $this->docblockHelper->shouldReceive('create')->with(null)->andReturn(null);
    $this->mockResolver
        ->shouldReceive('resolve')->once()
        ->with($this->fqcn)
        ->andReturn([$ns]);

    $result = $this->reflector->getReflection($this->fqcn);
    $cached = $this->reflector->getReflection($this->fqcn);
    expect($cached)->toBe($result);
});

it('builds from Namespace_ statement', function () {
    $cs = new Class_('MyClass');
    $ns = new Namespace_(new PhpParser\Node\Name('App\\Example'), [$cs]);
    $this->docblockHelper->shouldReceive('create')->with(null)->andReturn(null);

    $this->mockResolver
        ->shouldReceive('resolve')
        ->with($this->fqcn)
        ->andReturn([$ns]);

    $result = $this->reflector->getReflection($this->fqcn);

    expect($result)
        ->toBeInstanceOf(ObjectType::class)
        ->and($result->getFqcn())
        ->toBe($ns->name . '\\' . $cs->name);
});


<?php

use Fw2\Glimpse\Ast\AstResolver;
use Fw2\Glimpse\Providers\ParserProvider;
use PhpParser\Node\Stmt;
use PHPUnit\Framework\MockObject\MockObject;

beforeEach(function () {
    $this->parserProvider = mock(ParserProvider::class);
    $this->astResolver = new AstResolver($this->parserProvider);
});

it('throws exception if trying to resolve an internal class', function () {
    $internalClass = \stdClass::class;

    $this->parserProvider
        ->shouldNotReceive('get');

    expect(fn() => $this->astResolver->resolve($internalClass))
        ->toThrow(\LogicException::class, 'Source code for class "stdClass" is not found');
});

it('parses and returns statements for the AstResolver class', function () {
    $className = AstResolver::class;

    $mockedStatements = [new Stmt\Class_('AstResolver')];

    $parser = mock(PhpParser\Parser::class);
    $parser->shouldReceive('parse')
        ->andReturn($mockedStatements);

    $this->parserProvider
        ->shouldReceive('get')
        ->andReturn($parser);

    $result = $this->astResolver->resolve($className);

    expect($result)->toBe($mockedStatements);
});

it('caches parsed class AST for AstResolver class', function () {
    $className = AstResolver::class;
    $mockedStatements = [new Stmt\Class_('AstResolver')];

    $parser = mock(PhpParser\Parser::class);
    $parser->shouldReceive('parse')
        ->andReturn($mockedStatements);

    $this->parserProvider
        ->shouldReceive('get')
        ->andReturn($parser);

    $result = $this->astResolver->resolve($className);
    expect($result)->toBe($mockedStatements);

    $result = $this->astResolver->resolve($className);
    expect($result)->toBe($mockedStatements);
});

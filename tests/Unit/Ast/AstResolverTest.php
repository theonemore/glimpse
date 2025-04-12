<?php

use Fw2\Glimpse\Ast\AstResolver;
use Fw2\Glimpse\Providers\ParserProvider;
use PhpParser\Node\Stmt;
use PHPUnit\Framework\MockObject\MockObject;

beforeEach(function () {
    // Мокаем ParserProvider
    $this->parserProvider = mock(ParserProvider::class);
    $this->astResolver = new AstResolver($this->parserProvider);
});

it('throws exception if trying to resolve an internal class', function () {
    // Используем сам класс AstResolver, который точно не является внутренним
    $internalClass = \stdClass::class;

    $this->parserProvider
        ->shouldNotReceive('get') // метод не должен вызываться
    ;

    expect(fn() => $this->astResolver->resolve($internalClass))
        ->toThrow(\RuntimeException::class, sprintf('Resolving class can not be internal. %s given', $internalClass));
});

it('parses and returns statements for the AstResolver class', function () {
    $className = AstResolver::class; // используем сам класс AstResolver

    $mockedStatements = [new Stmt\Class_('AstResolver')]; // мокируем statement для AstResolver

    $parser = mock(PhpParser\Parser::class);
    $parser->shouldReceive('parse') // ожидаем вызов parse()
    ->andReturn($mockedStatements); // возвращаем mock statements при парсинге

    // Мокаем ParserProvider, чтобы он возвращал mock statements
    $this->parserProvider
        ->shouldReceive('get') // ожидаем вызов get()
        ->andReturn($parser) // возвращаем мок
    ;

    $result = $this->astResolver->resolve($className);

    expect($result)->toBe($mockedStatements);
});

it('caches parsed class AST for AstResolver class', function () {
    $className = AstResolver::class; // используем сам класс AstResolver
    $mockedStatements = [new Stmt\Class_('AstResolver')]; // мокируем statement для AstResolver

    $parser = mock(PhpParser\Parser::class);
    $parser->shouldReceive('parse')
        ->andReturn($mockedStatements);

    // Мокаем ParserProvider
    $this->parserProvider
        ->shouldReceive('get')
        ->andReturn($parser);

    // Первый вызов, парсим класс
    $result = $this->astResolver->resolve($className);
    expect($result)->toBe($mockedStatements);

    // Второй вызов, результат должен быть тот же, без повторного парсинга
    $result = $this->astResolver->resolve($className);
    expect($result)->toBe($mockedStatements);
});

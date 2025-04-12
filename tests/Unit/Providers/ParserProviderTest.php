<?php

use Fw2\Glimpse\Providers\ParserProvider;
use PhpParser\Parser;
use PhpParser\ParserFactory;

beforeEach(function () {
    $this->parserFactory = mock(ParserFactory::class);
    $this->parserProvider = new ParserProvider($this->parserFactory);
});

it('returns a Parser instance when get() is called', function () {
    $parser = mock(Parser::class);

    $this->parserFactory
        ->shouldReceive('createForHostVersion')
        ->andReturn($parser);

    $result = $this->parserProvider->get();
    expect($result)->toBe($parser);
});

it('returns the same Parser instance for multiple calls', function () {
    $parser = mock(Parser::class);

    $this->parserFactory
        ->shouldReceive('createForHostVersion')
        ->andReturn($parser);

    $result1 = $this->parserProvider->get();
    expect($result1)->toBe($parser);
    $result2 = $this->parserProvider->get();
    expect($result2)->toBe($result1);
});

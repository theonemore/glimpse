<?php

use Fw2\Glimpse\Providers\EvaluatorProvider;
use Fw2\Glimpse\Builder\ScalarExpressionEvaluator;
use Fw2\Glimpse\Providers\ParserProvider;

it('creates and caches ScalarExpressionEvaluator', function () {
    $parserProvider = mock(ParserProvider::class);
    $parserProvider->shouldReceive('get')->once()->andReturn(mock(PhpParser\Parser::class));

    $provider = new EvaluatorProvider($parserProvider);

    $evaluator1 = $provider->get();
    $evaluator2 = $provider->get();

    expect($evaluator1)->toBeInstanceOf(ScalarExpressionEvaluator::class)
        ->and($evaluator2)->toBe($evaluator1);
});

it('passes parser to evaluator', function () {
    $parser = mock(PhpParser\Parser::class);
    $parserProvider = mock(ParserProvider::class);
    $parserProvider->shouldReceive('get')->andReturn($parser);

    $provider = new EvaluatorProvider($parserProvider);
    $evaluator = $provider->get();

    $reflection = new ReflectionClass($evaluator);
    $property = $reflection->getProperty('parser');
    $property->setAccessible(true);

    expect($property->getValue($evaluator))->toBe($parser);
});
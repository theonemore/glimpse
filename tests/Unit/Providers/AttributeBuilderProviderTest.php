<?php

use Fw2\Glimpse\Builder\ScalarExpressionEvaluator;
use Fw2\Glimpse\Providers\AttributeBuilderProvider;
use Fw2\Glimpse\Builder\AttributeBuilder;
use Fw2\Glimpse\Providers\EvaluatorProvider;

beforeEach(function () {
    $this->evaluatorProvider = mock(EvaluatorProvider::class);
    $this->provider = new AttributeBuilderProvider($this->evaluatorProvider);
});

afterEach(fn() => Mockery::close());

it('creates AttributeBuilder instance only once', function () {
    $evaluatorMock = mock(ScalarExpressionEvaluator::class);

    $this->evaluatorProvider->shouldReceive('get')
        ->once()
        ->andReturn($evaluatorMock);

    $builder1 = $this->provider->create();
    $builder2 = $this->provider->create();

    expect($builder1)->toBeInstanceOf(AttributeBuilder::class)
        ->and($builder2)->toBe($builder1);
});

it('passes correct evaluator to AttributeBuilder', function () {
    $evaluatorMock = mock(ScalarExpressionEvaluator::class);

    $this->evaluatorProvider->shouldReceive('get')
        ->once()
        ->andReturn($evaluatorMock);

    $builder = $this->provider->create();

    $reflection = new ReflectionClass($builder);
    $property = $reflection->getProperty('evaluator');
    $property->setAccessible(true);

    expect($property->getValue($builder))->toBe($evaluatorMock);
});

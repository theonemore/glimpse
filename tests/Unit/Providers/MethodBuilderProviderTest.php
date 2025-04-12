<?php

use Fw2\Glimpse\Builder\AttributeBuilder;
use Fw2\Glimpse\Builder\TypeBuilder;
use Fw2\Glimpse\Providers\MethodBuilderProvider;
use Fw2\Glimpse\Builder\MethodBuilder;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Builder\DocBlockHelper;
use Fw2\Glimpse\Providers\TypeBuilderProvider;
use Fw2\Glimpse\Providers\AttributeBuilderProvider;

beforeEach(function () {
    $this->typeBuilderFactory = mock(TypeBuilderProvider::class);
    $this->attributeBuilderFactory = mock(AttributeBuilderProvider::class);
    $this->docHelper = mock(DocBlockHelper::class);
    $this->methodBuilderProvider = new MethodBuilderProvider(
        $this->typeBuilderFactory,
        $this->attributeBuilderFactory,
        $this->docHelper
    );
});

it('returns a MethodBuilder object when get() is called', function () {
    $reflector = mock(Reflector::class);
    $this->typeBuilderFactory
        ->shouldReceive('get')
        ->andReturn(mock(TypeBuilder::class));

    $this->attributeBuilderFactory
        ->shouldReceive('create')
        ->andReturn(mock(AttributeBuilder::class));

    $result = $this->methodBuilderProvider->get($reflector);
    expect($result)->toBeInstanceOf(MethodBuilder::class);
});

it('returns the same MethodBuilder instance for the same Reflector', function () {
    $reflector = mock(Reflector::class);

    $this->typeBuilderFactory
        ->shouldReceive('get')
        ->andReturn(mock(TypeBuilder::class));

    $this->attributeBuilderFactory
        ->shouldReceive('create')
        ->andReturn(mock(AttributeBuilder::class));

    $result1 = $this->methodBuilderProvider->get($reflector);
    expect($result1)->toBeInstanceOf(MethodBuilder::class);

    $result2 = $this->methodBuilderProvider->get($reflector);
    expect($result2)->toBe($result1);
});

it('correctly passes dependencies to MethodBuilder', function () {
    $reflector = mock(Reflector::class);

    $this->typeBuilderFactory
        ->shouldReceive('get')
        ->with($reflector)
        ->andReturn(mock(TypeBuilder::class));

    $this->attributeBuilderFactory
        ->shouldReceive('create')
        ->andReturn(mock(AttributeBuilder::class));

    $this->methodBuilderProvider->get($reflector);

    $this->typeBuilderFactory->shouldHaveReceived('get', [$reflector]);
    $this->attributeBuilderFactory->shouldHaveReceived('create');
});

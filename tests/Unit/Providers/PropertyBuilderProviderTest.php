<?php

use Fw2\Glimpse\Builder\AttributeBuilder;
use Fw2\Glimpse\Builder\TypeBuilder;
use Fw2\Glimpse\Providers\PropertyBuilderProvider;
use Fw2\Glimpse\Builder\PropertyBuilder;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Builder\DocBlockHelper;
use Fw2\Glimpse\Providers\TypeBuilderProvider;
use Fw2\Glimpse\Providers\AttributeBuilderProvider;

beforeEach(function () {
    $this->typeBuilderFactory = mock(TypeBuilderProvider::class);
    $this->attributeBuilderFactory = mock(AttributeBuilderProvider::class);
    $this->blockHelper = mock(DocBlockHelper::class);
    $this->propertyBuilderProvider = new PropertyBuilderProvider(
        $this->typeBuilderFactory,
        $this->attributeBuilderFactory,
        $this->blockHelper
    );
});

it('returns a PropertyBuilder instance when get() is called', function () {
    $reflector = mock(Reflector::class);

    $this->typeBuilderFactory
        ->shouldReceive('get')
        ->andReturn(mock(TypeBuilder::class));

    $this->attributeBuilderFactory
        ->shouldReceive('create')
        ->andReturn(mock(AttributeBuilder::class));;

    $result = $this->propertyBuilderProvider->get($reflector);
    expect($result)->toBeInstanceOf(PropertyBuilder::class);
});

it('returns the same PropertyBuilder instance for multiple calls with the same Reflector', function () {
    $reflector = mock(Reflector::class);

    $this->typeBuilderFactory
        ->shouldReceive('get')
        ->andReturn(mock(TypeBuilder::class));

    $this->attributeBuilderFactory
        ->shouldReceive('create')
        ->andReturn(mock(AttributeBuilder::class));

    $result1 = $this->propertyBuilderProvider->get($reflector);
    expect($result1)->toBeInstanceOf(PropertyBuilder::class);

    $result2 = $this->propertyBuilderProvider->get($reflector);
    expect($result2)->toBe($result1);
});

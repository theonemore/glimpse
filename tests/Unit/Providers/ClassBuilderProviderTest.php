<?php

use Fw2\Glimpse\Providers\ClassBuilderProvider;
use Fw2\Glimpse\Builder\ClassBuilder;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Providers\AttributeBuilderProvider;
use Fw2\Glimpse\Providers\MethodBuilderProvider;
use Fw2\Glimpse\Providers\PropertyBuilderProvider;

it('creates ClassBuilder only once and caches it', function () {
    $reflector = mock(Reflector::class);
    $attributeBuilderFactory = mock(AttributeBuilderProvider::class);
    $methodBuilderFactory = mock(MethodBuilderProvider::class);
    $propertyBuilderFactory = mock(PropertyBuilderProvider::class);

    $attributeBuilderFactory->shouldReceive('create')
        ->once()
        ->andReturn(mock(Fw2\Glimpse\Builder\AttributeBuilder::class));

    $methodBuilderFactory->shouldReceive('get')
        ->once()
        ->with($reflector)
        ->andReturn(mock(Fw2\Glimpse\Builder\MethodBuilder::class));

    $propertyBuilderFactory->shouldReceive('get')
        ->once()
        ->with($reflector)
        ->andReturn(mock(Fw2\Glimpse\Builder\PropertyBuilder::class));

    $provider = new ClassBuilderProvider(
        $attributeBuilderFactory,
        $methodBuilderFactory,
        $propertyBuilderFactory
    );

    $builder1 = $provider->get($reflector);
    $builder2 = $provider->get($reflector);

    expect($builder1)->toBeInstanceOf(ClassBuilder::class)
        ->and($builder2)->toBe($builder1);
});

it('passes correct dependencies to ClassBuilder', function () {
    $reflector = mock(Reflector::class);
    $attributeBuilder = mock(Fw2\Glimpse\Builder\AttributeBuilder::class);
    $methodBuilder = mock(Fw2\Glimpse\Builder\MethodBuilder::class);
    $propertyBuilder = mock(Fw2\Glimpse\Builder\PropertyBuilder::class);

    $attributeBuilderFactory = mock(AttributeBuilderProvider::class);
    $methodBuilderFactory = mock(MethodBuilderProvider::class);
    $propertyBuilderFactory = mock(PropertyBuilderProvider::class);

    $attributeBuilderFactory->shouldReceive('create')
        ->andReturn($attributeBuilder);

    $methodBuilderFactory->shouldReceive('get')
        ->with($reflector)
        ->andReturn($methodBuilder);

    $propertyBuilderFactory->shouldReceive('get')
        ->with($reflector)
        ->andReturn($propertyBuilder);

    $provider = new ClassBuilderProvider(
        $attributeBuilderFactory,
        $methodBuilderFactory,
        $propertyBuilderFactory
    );

    $builder = $provider->get($reflector);

    $reflection = new ReflectionClass($builder);

    $attributeBuilderProp = $reflection->getProperty('attributes');
    $attributeBuilderProp->setAccessible(true);

    $methodBuilderProp = $reflection->getProperty('methods');
    $methodBuilderProp->setAccessible(true);

    $propertyBuilderProp = $reflection->getProperty('properties');
    $propertyBuilderProp->setAccessible(true);

    $reflectorProp = $reflection->getProperty('reflector');
    $reflectorProp->setAccessible(true);

    expect($attributeBuilderProp->getValue($builder))->toBe($attributeBuilder)
        ->and($methodBuilderProp->getValue($builder))->toBe($methodBuilder)
        ->and($propertyBuilderProp->getValue($builder))->toBe($propertyBuilder)
        ->and($reflectorProp->getValue($builder))->toBe($reflector);
});

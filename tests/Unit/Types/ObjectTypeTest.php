<?php

use Fw2\Glimpse\Types\ObjectType;
use Fw2\Glimpse\Entity\ObjectMethod;
use Fw2\Glimpse\Entity\ObjectProperty;

it('can get FQCN', function () {
    $objectType = new ObjectType('App\\Models\\User');

    expect($objectType->getFqcn())->toBe('App\\Models\\User');
});

it('returns false for isScalar', function () {
    $objectType = new ObjectType('App\\Models\\User');

    expect($objectType->isScalar())->toBeFalse();
});

it('can add and retrieve methods', function () {
    $objectType = new ObjectType('App\\Models\\User');
    $method = new ObjectMethod('getName', 'App\\Models\\User');

    $objectType->addMethod($method);

    expect($objectType->getMethods())->toHaveKey('getName')
        ->and($objectType->getMethod('getName'))->toBe($method)
        ->and($objectType->hasSameMethod($method))->toBeTrue();
});

it('can add and retrieve properties', function () {
    $objectType = new ObjectType('App\\Models\\User');
    $property = new ObjectProperty('name', null, 'App\\Models\\User');

    $objectType->addProperty($property);

    expect($objectType->getProperties())->toHaveKey('name')
        ->and($objectType->getProperty('name'))->toBe($property)
        ->and($objectType->hasSameProperty($property))->toBeTrue();
});

it('returns empty arrays for new instance', function () {
    $objectType = new ObjectType('App\\Models\\User');

    expect($objectType->getMethods())->toBeEmpty()
        ->and($objectType->getProperties())->toBeEmpty();
});

it('returns null when getting non-existent method', function () {
    $objectType = new ObjectType('App\\Models\\User');

    expect($objectType->getMethod('nonExistent'))->toBeNull();
});

it('returns null  getting non-existent property', function () {
    $objectType = new ObjectType('App\\Models\\User');

    expect($objectType->getProperty('nonExistent'))->toBeNull();
});

it('can check for non-existent methods', function () {
    $objectType = new ObjectType('App\\Models\\User');
    $method = new ObjectMethod('nonExistent', 'App\\Models\\User');

    expect($objectType->hasSameMethod($method))->toBeFalse();
});

it('can check for non-existent properties', function () {
    $objectType = new ObjectType('App\\Models\\User');
    $property = new ObjectProperty('nonExistent', null, 'App\\Models\\User');

    expect($objectType->hasSameProperty($property))->toBeFalse();
});

it('can handle description', function () {
    $description = 'User model';
    $objectType = new ObjectType('App\\Models\\User', $description);

    expect($objectType->getDescription())->toBe($description);
});
<?php

use Fw2\Glimpse\Entity\ObjectProperty;
use Fw2\Glimpse\Types\IntType;
use Fw2\Glimpse\Types\StringType;
use Fw2\Glimpse\Entity\Attribute;

it('creates property with name and type', function () {
    $type = new StringType('string');
    $property = new ObjectProperty('propertyName', $type, 'TestClass');

    expect($property->getName())->toBe('propertyName')
        ->and($property->type)->toBe($type);
});

it('clones the property correctly', function () {
    $type = new IntType();
    $property = new ObjectProperty('propertyName', $type, 'TestClass');

    $clonedProperty = $property->clone();

    expect($clonedProperty)->not()->toBe($property)
        ->and($clonedProperty->getName())->toBe($property->getName())
        ->and($clonedProperty->type)->toBe($property->type);
});

it('uses HasAttributes trait for attributes', function () {
    $property = new ObjectProperty('propertyName', null, 'TestClass');

    $attribute = new Attribute('AttributeClass');
    $property->addAttribute($attribute);

    expect($property->getAttributes())->toHaveCount(1)
        ->and($property->getAttributes()[0])->toBe($attribute);
});

it('uses HasInfo trait for description and summary', function () {
    $property = new ObjectProperty('propertyName', null, 'TestClass');

    $property->setDescription('This is a property description');
    $property->setSummary('This is a summary');

    expect($property->getDescription())->toBe('This is a property description')
        ->and($property->getSummary())->toBe('This is a summary');
});

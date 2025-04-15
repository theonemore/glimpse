<?php

use Fw2\Glimpse\Entity\Attribute;
use Fw2\Glimpse\Entity\ObjectMethod;
use Fw2\Glimpse\Entity\Parameter;
use Fw2\Glimpse\Types\StringType;

it('adds parameters correctly', function () {
    $method = new ObjectMethod('testMethod', 'TestClass');
    $parameter = new Parameter('param1', new StringType());

    $method->addParameter($parameter);

    expect($method->getParameters())->toHaveCount(1)
        ->and($method->getParameters()['param1'])->toBe($parameter);
});

it('sets and gets return type correctly', function () {
    $method = new ObjectMethod('testMethod', 'TestClass');

    $type = new StringType('string');
    $method->setReturnType($type);

    expect($method->getReturnType())->toBe($type);
});

it('clones the method with a new name correctly', function () {
    $method = new ObjectMethod('originalMethod', 'TestClass');

    $parameter = new Parameter('param1', new StringType());
    $method->addParameter($parameter);

    $clonedMethod = $method->withName('clonedMethod');

    expect($clonedMethod->getName())->toBe('clonedMethod')
        ->and($clonedMethod->getParameters())->toHaveCount(1)
        ->and($clonedMethod->getParameters()['param1']->getName())->toBe($parameter->getName())
        ->and($clonedMethod->getParameters()['param1'])->not()->toBe($parameter)
    ;

});

it('uses HasAttributes trait for attributes', function () {
    $method = new ObjectMethod('testMethod', 'TestClass');

    $attribute = new Attribute('AttributeClass');
    $method->addAttribute($attribute);

    expect($method->getAttributes())->toHaveCount(1)
        ->and($method->getAttributes()[0])->toBe($attribute);
});

it('uses HasInfo trait for description and summary', function () {
    $method = new ObjectMethod('testMethod', 'TestClass');

    $method->setDescription('This is a method description');
    $method->setSummary('This is a summary');

    expect($method->getDescription())->toBe('This is a method description')
        ->and($method->getSummary())->toBe('This is a summary');
});

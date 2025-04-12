<?php

use Fw2\Glimpse\Entity\ObjectMethod;
use Fw2\Glimpse\Entity\ObjectProperty;
use Fw2\Glimpse\Entity\PromiseObject;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\ObjectType;
use Fw2\Glimpse\Types\StringType;
use Fw2\Glimpse\Entity\Attribute;

it('adds a method to resolved object', function () {
    $reflector = mock(Reflector::class);
    $object = new PromiseObject('TestClass', $reflector);
    $method = new ObjectMethod('testMethod');

    $resolvedObject = mock(ObjectType::class);
    $reflector->shouldReceive('reflect')->andReturn($resolvedObject);
    $resolvedObject->shouldReceive('addMethod')->with($method);

    $object->addMethod($method);

    expect($resolvedObject)->shouldHaveReceived('addMethod')->with($method);
});

it('adds a property to resolved object', function () {
    $reflector = mock(Reflector::class);
    $object = new PromiseObject('TestClass', $reflector);
    $property = new ObjectProperty('propertyName', new StringType('string'));

    $resolvedObject = mock(ObjectType::class);
    $reflector->shouldReceive('reflect')->andReturn($resolvedObject);
    $resolvedObject->shouldReceive('addProperty')->with($property);

    $object->addProperty($property);

    expect($resolvedObject)->shouldHaveReceived('addProperty')->with($property);
});

it('gets methods from resolved object', function () {
    $reflector = mock(Reflector::class);
    $object = new PromiseObject('TestClass', $reflector);

    $resolvedObject = mock(ObjectType::class);
    $method = new ObjectMethod('testMethod');
    $resolvedObject->shouldReceive('getMethods')->andReturn([$method]);
    $reflector->shouldReceive('reflect')->andReturn($resolvedObject);

    $methods = $object->getMethods();

    expect($methods)->toHaveCount(1)
        ->and($methods[0]->getName())->toBe('testMethod');
});

it('gets properties from resolved object', function () {
    $reflector = mock(Reflector::class);
    $object = new PromiseObject('TestClass', $reflector);

    $resolvedObject = mock(ObjectType::class);
    $property = new ObjectProperty('propertyName', new StringType('string'));
    $resolvedObject->shouldReceive('getProperties')->andReturn([$property]);
    $reflector->shouldReceive('reflect')->andReturn($resolvedObject);

    $properties = $object->getProperties();

    expect($properties)->toHaveCount(1)
        ->and($properties[0]->getName())->toBe('propertyName');
});

it('gets a property from resolved object by name', function () {
    $reflector = mock(Reflector::class);
    $object = new PromiseObject('TestClass', $reflector);

    $resolvedObject = mock(ObjectType::class);
    $property = new ObjectProperty('propertyName', new StringType('string'));
    $resolvedObject->shouldReceive('getProperty')->with('propertyName')->andReturn($property);
    $reflector->shouldReceive('reflect')->andReturn($resolvedObject);

    $property = $object->getProperty('propertyName');

    expect($property->getName())->toBe('propertyName');
});

it('gets a method from resolved object by name', function () {
    $reflector = mock(Reflector::class);
    $object = new PromiseObject('TestClass', $reflector);

    $resolvedObject = mock(ObjectType::class);
    $method = new ObjectMethod('testMethod');
    $resolvedObject->shouldReceive('getMethod')->with('testMethod')->andReturn($method);
    $reflector->shouldReceive('reflect')->andReturn($resolvedObject);

    $method = $object->getMethod('testMethod');

    expect($method->getName())->toBe('testMethod');
});

it('adds an attribute to resolved object', function () {
    $reflector = mock(Reflector::class);
    $object = new PromiseObject('TestClass', $reflector);
    $attribute = new Attribute('AttributeClass');

    $resolvedObject = mock(ObjectType::class);
    $reflector->shouldReceive('reflect')->andReturn($resolvedObject);
    $resolvedObject->shouldReceive('addAttribute')->with($attribute);

    $object->addAttribute($attribute);

    expect($resolvedObject)->shouldHaveReceived('addAttribute')->with($attribute);
});

it('gets description from resolved object', function () {
    $reflector = mock(Reflector::class);
    $object = new PromiseObject('TestClass', $reflector);

    $resolvedObject = mock(ObjectType::class);
    $resolvedObject->shouldReceive('getDescription')->andReturn('Test class description');
    $reflector->shouldReceive('reflect')->andReturn($resolvedObject);

    $description = $object->getDescription();

    expect($description)->toBe('Test class description');
});

it('gets attributes from resolved object', function () {
    $reflector = mock(Reflector::class);
    $object = new PromiseObject('TestClass', $reflector);

    $resolvedObject = mock(ObjectType::class);
    $resolvedObject->shouldReceive('getAttributes')->andReturn([]);
    $reflector->shouldReceive('reflect')->andReturn($resolvedObject);

    $object->getAttributes();

    expect($resolvedObject)->shouldHaveReceived('getAttributes');
});

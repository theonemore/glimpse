<?php

use Fw2\Glimpse\Types\Entity\Attribute;

it('correctly initializes with fqcn and arguments', function () {
    $fqcn = \stdClass::class;
    $arguments = ['arg1', 'arg2'];
    $attribute = new Attribute($fqcn, $arguments);

    expect($attribute->fqcn)->toBe($fqcn)
        ->and($attribute->arguments)->toBe($arguments);
});


it('creates an instance with the correct fqcn and arguments', function () {
    $fqcn = \stdClass::class;
    $arguments = ['arg1', 'arg2'];

    $attribute = new Attribute($fqcn, $arguments);

    $instance = $attribute->getInstance();

    expect($instance)->toBeInstanceOf($fqcn)
        ->and($instance)->toBeInstanceOf(\stdClass::class);
});


it('correctly creates an instance with passed arguments', function () {
    class TestClassWithConstructor
    {
        public function __construct(
            public string $param1,
            public int $param2,
            public bool $param3
        ) {
        }
    }

    $fqcn = TestClassWithConstructor::class;
    $arguments = ['arg1', 42, true];

    $attribute = new Attribute($fqcn, $arguments);

    $instance = $attribute->getInstance();

    expect($instance)->toBeInstanceOf($fqcn)
        ->and($instance->param1)->toBe('arg1')
        ->and($instance->param2)->toBe(42)
        ->and($instance->param3)->toBeTrue();
});

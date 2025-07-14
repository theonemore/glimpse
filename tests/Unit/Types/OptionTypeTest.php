<?php

use Fw2\Glimpse\Types\IntType;
use Fw2\Glimpse\Types\ObjectType;
use Fw2\Glimpse\Types\OptionType;
use Fw2\Glimpse\Types\StringType;

it('can get description from of type', function () {
    $intType = mock(IntType::class);
    $intType->shouldReceive('getDescription')->andReturn('Integer type description');

    $optionType = new OptionType($intType);

    expect($optionType->getDescription())->toBe('Integer type description');
});

it('can get the of type', function () {
    $intType = new IntType();

    $optionType = new OptionType($intType);

    expect($optionType->getOf())->toBe($intType);
});


it('can get wrapped type', function () {
    $intType = new IntType();
    $optionType = new OptionType($intType);

    expect($optionType->getOf())->toBe($intType);
});

it('returns correct scalar check for option of scalar type', function () {
    $intType = new IntType();
    $optionType = new OptionType($intType);

    expect($optionType->isScalar())->toBeTrue();
});

it('returns correct scalar check for option of non-scalar type', function () {
    $objectType = new ObjectType('App\\Models\\User');
    $optionType = new OptionType($objectType);

    expect($optionType->isScalar())->toBeFalse();
});

it('delegates description to wrapped type', function () {
    $description = 'User age';
    $intType = new IntType(description: $description);
    $optionType = new OptionType($intType);

    expect($optionType->getDescription())->toBe($description);
});

it('returns null description when wrapped type has no description', function () {
    $intType = new IntType();
    $optionType = new OptionType($intType);

    expect($optionType->getDescription())->toBeNull();
});

it('correctly handles different wrapped types', function () {
    $stringType = new StringType(null, null, 'Username');
    $optionType = new OptionType($stringType);

    expect($optionType->getOf())->toBe($stringType)
        ->and($optionType->getDescription())->toBe('Username')
        ->and($optionType->isScalar())->toBeTrue();
});
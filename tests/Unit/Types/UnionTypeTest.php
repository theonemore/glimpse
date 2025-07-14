<?php

use Fw2\Glimpse\Types\ArrayType;
use Fw2\Glimpse\Types\IntType;
use Fw2\Glimpse\Types\ObjectType;
use Fw2\Glimpse\Types\StringType;
use Fw2\Glimpse\Types\UnionType;

it('can be initialized with multiple types', function () {
    $intType = new IntType();
    $stringType = new StringType();
    $unionType = new UnionType($intType, $stringType);
    $types = iterator_to_array($unionType->getIterator());

    expect($types)->toHaveCount(2)
        ->and($types[0])->toBe($intType)
        ->and($types[1])->toBe($stringType);
});

it('implements IteratorAggregate correctly', function () {
    $intType = new IntType();
    $stringType = new StringType();
    $unionType = new UnionType($intType, $stringType);

    expect($unionType)->toBeInstanceOf(IteratorAggregate::class);

    $collected = [];
    foreach ($unionType as $type) {
        $collected[] = $type;
    }

    expect($collected)->toHaveCount(2)
        ->and($collected[0])->toBe($intType)
        ->and($collected[1])->toBe($stringType);
});

it('returns true for isScalar when all types are scalar', function () {
    $intType = new IntType();
    $stringType = new StringType();
    $unionType = new UnionType($intType, $stringType);

    expect($unionType->isScalar())->toBeTrue();
});

it('returns false for isScalar when any type is not scalar', function () {
    $intType = new IntType();
    $objectType = new ObjectType('App\\Models\\User');
    $unionType = new UnionType($intType, $objectType);

    expect($unionType->isScalar())->toBeFalse();
});

it('returns false for isScalar with array of non-scalar types', function () {
    $objectType = new ObjectType('App\\Models\\User');
    $arrayType = new ArrayType($objectType);
    $unionType = new UnionType($arrayType);

    expect($unionType->isScalar())->toBeFalse();
});

it('handles empty union type correctly for isScalar', function () {
    $unionType = new UnionType();

    expect($unionType->isScalar())->toBeTrue(); // empty AND operation defaults to true
});

it('can contain complex type combinations', function () {
    $intType = new IntType();
    $stringType = new StringType();
    $objectType = new ObjectType('App\\Models\\User');
    $arrayType = new ArrayType($intType);

    $unionType = new UnionType($intType, $stringType, $objectType, $arrayType);
    $types = iterator_to_array($unionType->getIterator());

    expect($types)->toHaveCount(4)
        ->and($unionType->isScalar())->toBeFalse();
});

it('preserves order of types', function () {
    $types = [
        new IntType(),
        new StringType(),
        new ObjectType('App\\Models\\User'),
        new ArrayType(new IntType())
    ];

    $unionType = new UnionType(...$types);

    foreach ($unionType as $index => $type) {
        expect($type)->toBe($types[$index]);
    }
});

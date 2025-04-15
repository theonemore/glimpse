<?php

use Fw2\Glimpse\Types\ArrayType;
use Fw2\Glimpse\Types\IntType;
use Fw2\Glimpse\Types\Type;

it('can get the type of array elements', function () {
    $intType = new IntType();
    $arrayType = new ArrayType($intType);

    expect($arrayType->getOf())->toBe($intType);
});

it('returns correct scalar check for array of non-scalar types', function () {
    $mockType = mock(Type::class);
    $mockType->shouldReceive('isScalar')->andReturn(false);
    $arrayType = new ArrayType($mockType);

    expect($arrayType->isScalar())->toBeFalse();

    $mockType = mock(Type::class);
    $mockType->shouldReceive('isScalar')->andReturn(true);
    $arrayType = new ArrayType($mockType);

    expect($arrayType->isScalar())->toBeTrue();
});

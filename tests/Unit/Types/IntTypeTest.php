<?php

use Fw2\Glimpse\Types\IntType;

it('can get min value', function () {
    $intType = new IntType(min: 10);

    expect($intType->getMin())->toBe(10);
});

it('can get max value', function () {
    $intType = new IntType(max: 20);

    expect($intType->getMax())->toBe(20);
});

it('can get both min and max values', function () {
    $intType = new IntType(min: 10, max: 20);

    expect($intType->getMin())->toBe(10)
        ->and($intType->getMax())->toBe(20);
});

it('returns null for min when not set', function () {
    $intType = new IntType();

    expect($intType->getMin())->toBeNull();
});

it('returns null for max when not set', function () {
    $intType = new IntType();

    expect($intType->getMax())->toBeNull();
});
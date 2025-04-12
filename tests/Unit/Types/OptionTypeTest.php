<?php

use Fw2\Glimpse\Types\OptionType;
use Fw2\Glimpse\Types\IntType;

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
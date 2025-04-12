<?php

use Fw2\Glimpse\Types\Type;

it('can get description', function () {
    $type = new class('Type description') extends Type {
    };

    expect($type->getDescription())->toBe('Type description');
});

it('returns null when no description provided', function () {
    $type = new class() extends Type {
    };

    expect($type->getDescription())->toBeNull();
});

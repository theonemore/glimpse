<?php

use Fw2\Glimpse\Types\AbstractObjectType;

it('return isScalar => false', function () {
    $intType = new AbstractObjectType();
    expect($intType->isScalar())->toBeFalse();
});
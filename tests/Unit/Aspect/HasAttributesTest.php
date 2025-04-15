<?php

use Fw2\Glimpse\Entity\Aspect\HasAttributes;
use Fw2\Glimpse\Entity\Attribute;

it('adds and gets attributes', function () {
    $object = new class {
        use HasAttributes;
    };

    $attr1 = new Attribute('My\Attribute');
    $attr2 = new Attribute('Other\Attribute');

    $object->addAttribute($attr1);
    $object->addAttribute($attr2);

    expect($object->getAttributes())->toBe([$attr1, $attr2])
        ->and($object->getAttributes('My\Attribute'))->toBe([$attr1])
        ->and($object->getAttribute('My\Attribute'))->toBe($attr1)
        ->and($object->hasAttribute('My\Attribute'))->toBeTrue()
        ->and($object->getAttributes('Unknown'))->toBe([])
        ->and($object->getAttribute('Unknown'))->toBeNull()
        ->and($object->hasAttribute('Unknown'))->toBeFalse()
    ;
});

<?php


use Fw2\Glimpse\Types\Aspect\HasInfo;

it('sets and gets description and summary', function () {
    $object = new class {
        use HasInfo;
    };

    expect($object->getDescription())->toBeNull()
        ->and($object->getSummary())->toBeNull();

    $object->setDescription('This is a description');
    expect($object->getDescription())->toBe('This is a description');

    $object->setSummary('This is a summary');
    expect($object->getSummary())->toBe('This is a summary');

    $object->setDescription(null);
    $object->setSummary(null);

    expect($object->getDescription())->toBeNull()
        ->and($object->getSummary())->toBeNull();
});

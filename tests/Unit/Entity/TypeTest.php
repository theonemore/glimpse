<?php

use Fw2\Glimpse\Types\Type;

it('can get description', function () {
    $type = new class('Type description') extends Type {
        public function isScalar(): bool
        {
            return true;
        }

        public function getName(): string
        {
            return 'string';
        }
    };

    expect($type->getDescription())->toBe('Type description');
});

it('returns null when no description provided', function () {
    $type = new class() extends Type {
        public function isScalar(): bool
        {
            return true;
        }

        public function getName(): string
        {
            return 'string';
        }
    };

    expect($type->getDescription())->toBeNull();
});

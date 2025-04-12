<?php

use Fw2\Glimpse\Providers\TypeBuilderProvider;
use Fw2\Glimpse\Builder\TypeBuilder;
use Fw2\Glimpse\Reflector;
use PHPUnit\Framework\MockObject\MockObject;

beforeEach(function () {
    $this->typeBuilderProvider = new TypeBuilderProvider();
});

it('returns a TypeBuilder instance when get() is called', function () {
    $reflector = mock(Reflector::class);

    $result = $this->typeBuilderProvider->get($reflector);
    expect($result)->toBeInstanceOf(TypeBuilder::class);
});

it('returns the same TypeBuilder instance for multiple calls with the same Reflector', function () {
    $reflector = mock(Reflector::class);

    // Первый вызов
    $result1 = $this->typeBuilderProvider->get($reflector);
    expect($result1)->toBeInstanceOf(TypeBuilder::class);

    // Второй вызов — тот же объект
    $result2 = $this->typeBuilderProvider->get($reflector);
    expect($result2)->toBe($result1);
});

<?php

declare(strict_types=1);

namespace Fw2\Mentalist\Providers;

use Fw2\Mentalist\Builder\TypeBuilder;
use Fw2\Mentalist\Reflector;

class TypeBuilderProvider
{
    private TypeBuilder $builder;

    public function get(Reflector $reflector): TypeBuilder
    {
        return $this->builder ?? $this->builder = new TypeBuilder($reflector);
    }
}

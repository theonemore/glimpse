<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Providers;

use Fw2\Glimpse\Builder\TypeBuilder;
use Fw2\Glimpse\Reflector;

class TypeBuilderProvider
{
    private TypeBuilder $builder;

    public function get(Reflector $reflector): TypeBuilder
    {
        return $this->builder ?? $this->builder = new TypeBuilder($reflector);
    }
}

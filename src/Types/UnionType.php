<?php

declare(strict_types=1);

namespace Fw2\Mentalist\Types;

use IteratorAggregate;
use Traversable;

/**
 * @phpstan-implements IteratorAggregate<int, Type>
 */
class UnionType extends Type implements IteratorAggregate
{
    /**
     * @var array<int, Type>
     */
    public readonly array $types;

    public function __construct(Type ...$types)
    {
        parent::__construct();
        $this->types = $types;
    }

    /**
     * @return Traversable<int, Type>
     */
    public function getIterator(): Traversable
    {
        yield from $this->types;
    }
}

<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

use IteratorAggregate;
use Traversable;

/**
 * @phpstan-implements IteratorAggregate<int, Type>
 */
class IntersectionType extends Type implements IteratorAggregate
{
    /**
     * @var array<int, Type>
     */
    private readonly array $types;

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

    public function isScalar(): bool
    {
        return array_reduce($this->types, fn(bool $carry, Type $item) => $carry && $item->isScalar(), true);
    }

    public function getName(): string
    {
        return implode('|', array_map(fn(Type $type) => $type->getName(), $this->types));
    }
}

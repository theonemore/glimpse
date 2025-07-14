<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Types;

class DictType extends Type
{
    private StringType|IntType $key;
    private Type $type;

    public function __construct(Type $type = null, IntType|StringType $key = null, ?string $description = null)
    {
        $this->type = is_null($type) ? new MixedType() : $type;
        $this->key = is_null($key) ? new StringType() : $key;
        parent::__construct($description);
    }

    public function isScalar(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return 'dictionary';
    }

    public function getKey(): IntType|StringType
    {
        return $this->key;
    }

    public function getType(): Type
    {
        return $this->type;
    }
}

<?php

namespace Fw2\Glimpse;

use Fw2\Glimpse\Types\Type;

class Context
{
    /**
     * @var array<string, class-string>
     */
    private array $uses = [];

    /**
     * @var array<string>
     */
    private array $placeholders = [];

    /**
     * @var Type[]
     */
    private array $implementations = [];

    public function __construct(private ?string $ns = null)
    {
    }

    /**
     * @param string $alias
     * @return class-string
     */
    public function resolve(string $alias): string
    {
        return $this->uses[$alias] ?? (str_starts_with($alias, '\\') ? $alias : $this->resolveAlias($alias));
    }

    /**
     * @param string $alias
     * @param class-string $implementation
     * @return static
     */
    public function addAlias(string $alias, string $implementation): static
    {
        $this->uses[$alias] = $implementation;

        return $this;
    }

    public function addPlaceholder(string $alias): static
    {
        $this->placeholders[] = $alias;

        return $this;
    }

    public function implement(Type ...$implementations): static
    {
        foreach ($implementations as $place => $implementation) {
            $alias = $this->placeholders[$place] ?? null;
            if (!is_null($alias)) {
                $this->addImplementation($alias, $implementation);
            }
        }

        return $this;
    }

    /**
     * @param class-string $implementation
     * @return $this
     */
    public function setParent(string $implementation): static
    {
        $this->addAlias('parent', $implementation);
        return $this;
    }

    /**
     * @return class-string
     */
    public function getParent(): string
    {
        return $this->resolve('parent');
    }

    /**
     * @param class-string $implementation
     * @return $this
     */
    public function setSelf(string $implementation): static
    {
        $this->addAlias('self', $implementation);
        return $this;
    }

    /**
     * @param class-string $implementation
     * @return $this
     */
    public function setStatic(string $implementation): static
    {
        $this->setSelf($implementation); // TODO нужно ли разделить эти понятия так?
        $this->addAlias('static', $implementation);

        return $this;
    }

    public function copy(): static
    {
        return clone $this;
    }

    /**
     * @param string $input
     * @return array<int, string>
     */
    private function splitByFirstBackslash(string $input): array
    {
        $pos = strpos($input, '\\');

        if ($pos === false) {
            return [$input, ''];
        }

        return [substr($input, 0, $pos), substr($input, $pos + 1)];
    }

    private function resolveAlias(string $alias): string
    {
        [$anchor, $path] = $this->splitByFirstBackslash($alias);

        foreach ($this->uses as $key => $fqcn) {
            if ($key === $anchor) {
                return $this->uses[$alias] = implode('\\', array_filter([$fqcn, $path]));
            }
        }

        return $this->uses[$alias] = implode('\\', array_filter([$this->ns, $alias]));
    }

    private function addImplementation(mixed $alias, mixed $implementation): void
    {
        $this->implementations[$alias] = $implementation;
    }

    public function getImplementation(string $alias): ?Type
    {
        return $this->implementations[$alias] ?? null;
    }

    /**
     * @return class-string
     */
    public function getStatic(): string
    {
        return $this->resolve('static');
    }
}

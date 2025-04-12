<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Context;

use phpDocumentor\Reflection\Types\Context as PhpDocContext;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;

class Context
{
    /**
     * @var array<string, string>
     */
    private array $uses = [];
    private ?string $ns;
    private ?string $static = null;

    /**
     * @var array<string, string>
     */
    private array $cache = [];
    private ?PhpDocContext $phpDocContext = null;
    private ?string $parent = null;

    public function __construct(?Namespace_ $namespace = null)
    {
        $this->ns = $namespace?->name->name;

        if ($namespace) {
            foreach ($namespace->stmts as $stmt) {
                if ($stmt instanceof GroupUse) {
                    $this->addGroup($stmt);
                }

                if ($stmt instanceof Use_) {
                    $this->addUse($stmt);
                }
            }
        }
    }

    /**
     * @param class-string $static
     * @return static
     */
    public function for(string $static): static
    {
        return (clone $this)
            ->setStatic($this->fqcn($static));
    }

    private function add(ContextName $name): void
    {
        $this->uses[$name->alias] = $name->fqcn;
    }

    public function addUseItem(UseItem $use, ?Name $prefix = null): void
    {
        $this->add($this->buildContextName($use, $prefix));
    }

    public function addGroup(GroupUse $group): void
    {
        foreach ($group->uses as $use) {
            $this->addUseItem($use, $group->prefix);
        }
    }

    private function buildContextName(UseItem $use, ?Name $prefix): ContextName
    {
        $fqcn = $use->name->name;
        $alias = $use->alias?->name;
        $prefixName = $prefix?->name;

        [$contextPrefix, $contextAlias] = $this->splitByLastBackslash($fqcn);

        $alias = $alias ?? $contextAlias;
        $fqcn = implode('\\', array_filter([$prefixName, $contextPrefix, $contextAlias]));

        return new ContextName($fqcn, $alias);
    }

    /**
     * @param string $string
     * @return array<int, string>
     */
    public function splitByLastBackslash(string $string): array
    {
        $pos = strrpos($string, '\\');

        if ($pos === false) {
            return ['', $string];
        }

        return [substr($string, 0, $pos), substr($string, $pos + 1)];
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

    public function addUse(Use_ $stmt): void
    {
        foreach ($stmt->uses as $use) {
            $this->addUseItem($use);
        }
    }

    public function fqcn(string $alias): string
    {
        if (isset($this->cache[$alias])) {
            return $this->cache[$alias];
        }

        if (str_starts_with($alias, '\\')) {
            return $alias;
        }

        [$anchor, $path] = $this->splitByFirstBackslash($alias);

        foreach ($this->uses as $key => $fqcn) {
            if ($key === $anchor) {
                return $this->cache[$alias] = implode('\\', array_filter([$fqcn, $path]));
            }
        }

        return $this->cache[$alias] = implode('\\', array_filter([$this->ns, $alias]));
    }

    public function toPhpDoc(): PhpDocContext
    {
        return $this->phpDocContext ?? $this->phpDocContext = new PhpDocContext(
            $this->ns ?? '',
            $this->uses
        );
    }

    public function getStatic(): ?string
    {
        return $this->static;
    }

    public function setStatic(string $static): static
    {
        $this->static = $static;

        return $this;
    }

    public function setParent(string $name): static
    {
        $this->parent = $name;

        return $this;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }
}

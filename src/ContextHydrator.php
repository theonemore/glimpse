<?php

declare(strict_types=1);

namespace Fw2\Glimpse;

use Fw2\Glimpse\PhpDoc\DocBlockHelper;
use Fw2\Glimpse\Types\Type;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\UseItem;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;

class ContextHydrator
{
    public function __construct(
        private readonly DocBlockHelper $docs,
    ) {
    }

    public function hydrateNamespaceContext(Stmt\Namespace_ $namespace, Context $context): void
    {
        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof Stmt\GroupUse) {
                $this->hydrateGroupUse($stmt, $context);
            }

            if ($stmt instanceof Stmt\Use_) {
                $this->hydrateUse($stmt, $context);
            }
        }
    }

    /**
     * @param Stmt\ClassLike $classLikeStatement
     * @param Context $context
     * @param Type[] $implementations
     * @return void
     */
    public function hydrateClassContext(
        Stmt\ClassLike $classLikeStatement,
        Context $context,
        array $implementations
    ): void {
        $context->setStatic($context->resolve($classLikeStatement->name->name));

        if ($classLikeStatement instanceof Stmt\Class_ && $classLikeStatement->extends) {
            $context->setParent($context->resolve($classLikeStatement->extends->name));
        }

        $context->setParent($context->resolve($classLikeStatement->name->name));

        if ($comment = $this->docs->create($classLikeStatement->getDocComment()?->getText())) {
            foreach ($comment->children as $child) {
                if ($child instanceof PhpDocTagNode) {
                    $value = $child->value;
                    if ($value instanceof TemplateTagValueNode) {
                        // TODO default? $value->default
                        $context->addPlaceholder($value->name);
                    }
                }
            }
        }

        $context->implement(...$implementations);
    }

    private function hydrateGroupUse(Stmt\GroupUse $groupUseStatement, Context $context): void
    {
        foreach ($groupUseStatement->uses as $use) {
            $context->addAlias(...$this->buildContextName($use, $groupUseStatement->prefix));
        };
    }

    private function hydrateUse(Stmt\Use_ $useStatement, Context $context): void
    {
        foreach ($useStatement->uses as $use) {
            $context->addAlias(...$this->buildContextName($use));
        }
    }


    /**
     * @param UseItem $use
     * @param Name|null $prefix
     * @return array{string, string}
     */
    private function buildContextName(UseItem $use, ?Name $prefix = null): array
    {
        $fqcn = $use->name->name;
        $alias = $use->alias?->name;
        $prefixName = $prefix?->name;

        [$contextPrefix, $contextAlias] = $this->splitByLastBackslash($fqcn);

        $alias = $alias ?? $contextAlias;
        $fqcn = implode('\\', array_filter([$prefixName, $contextPrefix, $contextAlias]));

        return [$alias, $fqcn];
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
}

<?php

declare(strict_types=1);

namespace Fw2\Mentalist;

use Fw2\Mentalist\Ast\AstResolver;
use Fw2\Mentalist\Builder\ClassBuilder;
use Fw2\Mentalist\Builder\Context\Context;
use Fw2\Mentalist\Builder\DocBlockHelper;
use Fw2\Mentalist\Entity\PromiseObject;
use Fw2\Mentalist\Providers\AttributeBuilderProvider;
use Fw2\Mentalist\Providers\ClassBuilderProvider;
use Fw2\Mentalist\Providers\EvaluatorProvider;
use Fw2\Mentalist\Providers\MethodBuilderProvider;
use Fw2\Mentalist\Providers\ParserProvider;
use Fw2\Mentalist\Providers\PropertyBuilderProvider;
use Fw2\Mentalist\Providers\TypeBuilderProvider;
use Fw2\Mentalist\Types\ObjectType;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\ParserFactory;
use ReflectionException;

class Reflector
{
    private AstResolver $resolver;

    /**
     * @var array<string, ObjectType>
     */
    private array $built = [];
    private ClassBuilder $classes;

    public function __construct(
        AstResolver $resolver,
        ClassBuilderProvider $classBuilderProvider,
    ) {
        $this->resolver = $resolver;
        $this->classes = $classBuilderProvider->get($this);
    }

    /**
     * @throws ReflectionException
     */
    public function reflect(string $fqcn, bool $ref = false): ObjectType
    {
        if (!isset($this->built[$fqcn])) {
            if ($ref) {
                return new PromiseObject($fqcn, $this);
            }

            $ast = $this->resolver->resolve($fqcn);

            foreach ($ast as $stmt) {
                $this->buildStatement($stmt);
            }
        }


        return $this->built[$fqcn];
    }

    /**
     * @throws ReflectionException
     */
    private function buildNamespace(Namespace_ $ns): void
    {
        $ctx = new Context($ns);

        foreach ($ns->stmts as $stmt) {
            if ($stmt instanceof ClassLike) {
                $this->buildClass($stmt, $ctx->for($stmt->name->name));
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function buildClass(ClassLike $classLike, Context $ctx = null): void
    {
        $object = $this->classes->build($classLike, $ctx ?? new Context());
        $this->built[$object->getFqcn()] = $object;
    }

    /**
     * @throws ReflectionException
     */
    private function buildStatement(mixed $stmt): void
    {
        match (true) {
            $stmt instanceof Namespace_ => $this->buildNamespace($stmt),
            $stmt instanceof ClassLike => $this->buildClass($stmt),
            default => throw new \RuntimeException(),
        };
    }

    public static function createInstance(ParserFactory $parserFactory, DocBlockFactoryInterface $docBlockFactory): self
    {
        $parserProvider = new ParserProvider($parserFactory);
        $typeBuilderProvider = new TypeBuilderProvider();
        $attributeBuilderProvider = new AttributeBuilderProvider(new EvaluatorProvider($parserProvider));
        $docBlockHelper = new DocBlockHelper($docBlockFactory);

        return new self(
            new AstResolver($parserProvider),
            new ClassBuilderProvider(
                $attributeBuilderProvider,
                new MethodBuilderProvider(
                    $typeBuilderProvider,
                    $attributeBuilderProvider,
                    $docBlockHelper
                ),
                new PropertyBuilderProvider(
                    $typeBuilderProvider,
                    $attributeBuilderProvider,
                    $docBlockHelper
                ),
            ),
        );
    }
}

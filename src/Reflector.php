<?php

declare(strict_types=1);

namespace Fw2\Glimpse;

use Composer\Autoload\ClassLoader;
use Fw2\Glimpse\Ast\AstResolver;
use Fw2\Glimpse\Builders\AttributeBuilder;
use Fw2\Glimpse\Builders\DocTypeBuilder;
use Fw2\Glimpse\Builders\MethodBuilder;
use Fw2\Glimpse\Builders\ObjectTypeBuilder;
use Fw2\Glimpse\Builders\PhpTypeBuilder;
use Fw2\Glimpse\Builders\PropertyBuilder;
use Fw2\Glimpse\Builders\ScalarExpressionEvaluator;
use Fw2\Glimpse\PhpDoc\DocBlockHelper;
use Fw2\Glimpse\Types\ObjectType;
use Fw2\Glimpse\Types\PromiseObject;
use Fw2\Glimpse\Types\Type;
use PhpParser\Lexer;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\Parser\Php8 as PhpParser;
use PHPStan\PhpDocParser\Lexer\Lexer as PhpDocLexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use ReflectionException;

class Reflector
{
    private ObjectTypeBuilder $objectTypeBuilder;
    private ContextHydrator $contextHydrator;

    public function __construct(
        private AstResolver $ast,
        Parser $parser,
        DocBlockHelper $docBlockHelper,
    ) {
        $docTypeBuilder = new DocTypeBuilder($this);
        $phpTypeBuilder = new PhpTypeBuilder($this);
        $this->contextHydrator = new ContextHydrator($docBlockHelper);
        $this->objectTypeBuilder = new ObjectTypeBuilder(
            $attributes = new AttributeBuilder(new ScalarExpressionEvaluator($parser)),
            new MethodBuilder($docTypeBuilder, $phpTypeBuilder, $attributes, $docBlockHelper),
            new PropertyBuilder($docTypeBuilder, $phpTypeBuilder, $attributes, $docBlockHelper),
            $this
        );
    }

    /**
     * @var array<string, ObjectType<mixed>>
     */
    private array $built = [];

    /**
     * @template T
     *
     * @param class-string<T> $fqcn
     * @param array<Type> $implementations
     * @return ObjectType<T>
     * @throws ReflectionException
     */
    public function getReflection(string $fqcn, array $implementations = []): ObjectType
    {
        return $this->reflected($fqcn, $implementations) ?? $this->reflect($fqcn, $implementations);
    }

    /**
     * @template T
     * @param class-string<T> $fqcn
     * @param Type[] $implementations
     * @return ObjectType<T>
     * @throws ReflectionException
     */
    public function reflect(string $fqcn, array $implementations): ObjectType
    {
        $cacheKey = $fqcn . $this->genericSignature($implementations);
        $this->built[$cacheKey] = new PromiseObject($fqcn, $this, $implementations);

        $statements = $this->ast->resolve($fqcn);
        foreach ($statements as $statement) {
            $this->buildStatement($statement, $implementations);
        }

        return $this->built[$cacheKey];
    }

    /**
     * @template T
     *
     * @param class-string<T> $fqcn
     * @param Type[] $implementations
     * @return ObjectType<T>|null
     */
    public function reflected(string $fqcn, array $implementations): ?ObjectType
    {
        return $this->built[$fqcn . $this->genericSignature($implementations)] ?? null;
    }

    /**
     * @param Stmt\Namespace_ $statement
     * @param Type[] $implementations
     * @return void
     * @throws ReflectionException
     */
    private function buildNamespace(Stmt\Namespace_ $statement, array $implementations): void
    {
        $this->contextHydrator->hydrateNamespaceContext(
            $statement,
            $context = new Context($statement->name->toString())
        );

        foreach ($statement->stmts as $stmt) {
            if ($stmt instanceof Stmt\ClassLike) {
                $this->buildClassLike($stmt, $implementations, $context->copy());
            }
        }
    }

    /**
     * @param Stmt $statement
     * @param Type[] $implementations
     * @return void
     * @throws ReflectionException
     */
    private function buildStatement(Stmt $statement, array $implementations): void
    {
        switch (true) {
            case $statement instanceof Stmt\Namespace_:
                $this->buildNamespace($statement, $implementations);
                break;
            case $statement instanceof Stmt\ClassLike:
                $this->buildClassLike($statement, $implementations);
                break;
            default:
                break;
        }
    }

    /**
     * @param Stmt\ClassLike $classLikeStatement
     * @param Type[] $implementations
     * @param Context|null $context
     * @return void
     * @throws ReflectionException
     */
    private function buildClassLike(
        Stmt\ClassLike $classLikeStatement,
        array $implementations,
        ?Context $context = null
    ): void {
        $context = $context ?? new Context();
        $this->contextHydrator->hydrateClassContext($classLikeStatement, $context, $implementations);

        $cacheKey = $context->resolve($classLikeStatement->name->name) . $this->genericSignature($implementations);

        $this->built[$cacheKey] = $this->objectTypeBuilder->build(
            $classLikeStatement,
            $context
        );
    }

    /**
     * @param Type[] $implementations
     * @return string
     */
    private function genericSignature(array $implementations): string
    {
        return !empty($implementations)
            ? sprintf('<%s>', implode(', ', array_map(fn(Type $t) => $t->getName(), $implementations)))
            : '';
    }

    public static function create(): self
    {
        $parser = new PhpParser(new Lexer());
        $phpDocParserConfig = new ParserConfig([]);
        $constExprParser = new ConstExprParser($phpDocParserConfig);

        return new self(
            new AstResolver($parser, ClassLoader::getRegisteredLoaders()),
            $parser,
            new DocBlockHelper(
                new PhpDocParser(
                    $phpDocParserConfig,
                    new TypeParser($phpDocParserConfig, $constExprParser),
                    $constExprParser,
                ),
                new PhpDocLexer($phpDocParserConfig),
            ),
        );
    }
}

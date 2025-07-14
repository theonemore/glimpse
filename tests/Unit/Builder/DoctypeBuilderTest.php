<?php


use Fw2\Glimpse\Builders\DocTypeBuilder;
use Fw2\Glimpse\Context;
use Fw2\Glimpse\Reflector;
use Fw2\Glimpse\Types\ArrayType;
use Fw2\Glimpse\Types\CallableType;
use Fw2\Glimpse\Types\IntersectionType;
use Fw2\Glimpse\Types\IntType;
use Fw2\Glimpse\Types\NullType;
use Fw2\Glimpse\Types\ObjectType;
use Fw2\Glimpse\Types\OptionType;
use Fw2\Glimpse\Types\StringType;
use Fw2\Glimpse\Types\UnionType;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\InvalidTypeNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayItemNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ParserException;

beforeEach(function () {
    $reflectorMock = mock(Reflector::class);
    $reflectorMock->shouldReceive('getReflection')
        ->with('TestClass')
        ->andReturn(new ObjectType('TestClass'));
    $reflectorMock->shouldReceive('getReflection')
        ->with('TestClass', Mockery::type('array'))
        ->andReturn(new ObjectType('TestClass'));
    $reflectorMock->shouldReceive('getReflection')
        ->with('Namespace\\SomeClass')
        ->andReturn(new ObjectType('Namespace\\SomeClass'));
    $reflectorMock->shouldReceive('getReflection')
        ->with('Namespace\\SomeClass', Mockery::type('array'))
        ->andReturn(new ObjectType('Namespace\\SomeClass'));
    $reflectorMock->shouldReceive('getReflection')
        ->with('\\Fully\\Qualified\\Class')
        ->andReturn(new ObjectType('Fully\\Qualified\\Class'));
    $reflectorMock->shouldReceive('getReflection')
        ->with('\\Fully\\Qualified\\Class', Mockery::type('array'))
        ->andReturn(new ObjectType('Fully\\Qualified\\Class'));

    $this->builder = new DocTypeBuilder($reflectorMock);
    $this->ctx = new Context();
});

it('builds special integer types', function () {
    $positiveInt = new IdentifierTypeNode('positive-int');
    /** @var IntType $result */
    $result = $this->builder->build($positiveInt, $this->ctx);

    expect($result)->toBeInstanceOf(IntType::class)
        ->and($result->getMin())->toBe(1);
});

it('builds array types', function () {
    $arrayType = new GenericTypeNode(
        new IdentifierTypeNode('array'),
        [new IdentifierTypeNode('string')]
    );
    /** @var ArrayType $result */
    $result = $this->builder->build($arrayType, $this->ctx);

    expect($result)->toBeInstanceOf(ArrayType::class)
        ->and($result->getOf())->toBeInstanceOf(StringType::class);
});

it('builds ObjectShapeNode', function () {
    $node = new ObjectShapeNode([
        new ObjectShapeItemNode(new ConstExprStringNode('foo', 1), false, new IdentifierTypeNode('int')),
    ]);
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(ObjectType::class);
});

it('builds ConditionalTypeNode', function () {
    $node = new ConditionalTypeNode(
        new IdentifierTypeNode('int'),
        new IdentifierTypeNode('string'),
        new IdentifierTypeNode('float'),
        new IdentifierTypeNode('float'),
        false,
    );
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(UnionType::class);
});

it('builds ArrayShapeNode', function () {
    $node = ArrayShapeNode::createSealed([
        new ArrayShapeItemNode(
            new IdentifierTypeNode('string'), false, new IdentifierTypeNode('string')
        ),
    ]);
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(ObjectType::class);
});

it('builds ArrayTypeNode', function () {
    $node = new ArrayTypeNode(new IdentifierTypeNode('int'));
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(ArrayType::class);
});

it('builds UnionTypeNode', function () {
    $node = new UnionTypeNode([
        new IdentifierTypeNode('int'),
        new IdentifierTypeNode('string'),
    ]);
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(UnionType::class);
});

it('builds IntersectionTypeNode', function () {
    $node = new IntersectionTypeNode([
        new IdentifierTypeNode('int'),
        new IdentifierTypeNode('string'),
    ]);
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(IntersectionType::class);
});

it('builds NullableTypeNode', function () {
    $node = new NullableTypeNode(new IdentifierTypeNode('int'));
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(OptionType::class);
});

it('builds ConstTypeNode (int)', function () {
    $node = new ConstTypeNode(new ConstExprIntegerNode('42'));
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(IntType::class)
        ->and($result->getValue())->toBe(42);
});

it('builds ConstTypeNode (string)', function () {
    $node = new ConstTypeNode(new ConstExprStringNode('hello', 1));
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(StringType::class)
        ->and($result->getValue())->toBe('hello');
});

it('builds ConstTypeNode (null)', function () {
    $node = new ConstTypeNode(new ConstExprNullNode());
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(NullType::class)
        ->and($result->getValue())->toBeNull();
});

it('builds ConstTypeNode (true/false)', function () {
    $nodeTrue = new ConstTypeNode(new ConstExprTrueNode());
    $nodeFalse = new ConstTypeNode(new ConstExprFalseNode());
    $resultTrue = $this->builder->build($nodeTrue, $this->ctx);
    $resultFalse = $this->builder->build($nodeFalse, $this->ctx);
    expect($resultTrue->getValue())->toBeTrue()
        ->and($resultFalse->getValue())->toBeFalse();
});

it('builds ArrayExprNode to ObjectType type', function () {
    $arrayNode = new ConstExprArrayNode([
        new ConstExprArrayItemNode(new ConstExprIntegerNode('1'), new ConstExprStringNode('a', 1)),
        new ConstExprArrayItemNode(new ConstExprIntegerNode('2'), new ConstExprStringNode('b', 1)),
    ]);
    $node = new ConstTypeNode($arrayNode);
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(ObjectType::class);
});

it('builds CallableTypeNode', function () {
    $node = new CallableTypeNode(
        new IdentifierTypeNode('callable'), // identifier
        [], // parameters
        new IdentifierTypeNode('void'), // returnType
        [] // templateTypes
    );
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(CallableType::class);
});

it('builds ThisTypeNode', function () {
    $this->ctx->setStatic('TestClass');
    $node = new ThisTypeNode();
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(ObjectType::class);
});

it('throws on InvalidTypeNode', function () {
    $node = new InvalidTypeNode(
        new ParserException('test', Lexer::TOKEN_PHPDOC_EOL, 0, 0, Lexer::TOKEN_WILDCARD, null)
    );
    expect(fn() => $this->builder->build($node, $this->ctx))->toThrow(Exception::class);
});

it('throws on unknown type', function () {
    $node = new class implements TypeNode {
        public function __toString(): string
        {
            return 'unknown';
        }

        public function setAttribute(string $key, $value): void
        {
        }

        public function hasAttribute(string $key): bool
        {
            return false;
        }

        public function getAttribute(string $key)
        {
        }
    };
    expect(fn() => $this->builder->build($node, $this->ctx))->toThrow(Exception::class);
});

it('throws on unimplemented generic type', function () {
    $node = new GenericTypeNode(
        new IdentifierTypeNode('array'),
        [new IdentifierTypeNode('int'), new IdentifierTypeNode('string'), new IdentifierTypeNode('float')]
    );
    expect(fn() => $this->builder->build($node, $this->ctx))->toThrow(Exception::class);
});

it('builds generic int with min/max', function () {
    $node = new GenericTypeNode(
        new IdentifierTypeNode('int'),
        [new ConstTypeNode(new ConstExprIntegerNode('1')), new ConstTypeNode(new ConstExprIntegerNode('10'))]
    );
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(IntType::class)
        ->and($result->getMin())->toBe(1)
        ->and($result->getMax())->toBe(10);
});

it('builds generic string with min/max', function () {
    $node = new GenericTypeNode(
        new IdentifierTypeNode('string'),
        [new ConstTypeNode(new ConstExprIntegerNode('1')), new ConstTypeNode(new ConstExprIntegerNode('10'))]
    );
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(StringType::class)
        ->and($result->getMin())->toBe(1)
        ->and($result->getMax())->toBe(10);
});

it('builds generic object', function () {
    $node = new GenericTypeNode(new IdentifierTypeNode('TestClass'), [new IdentifierTypeNode('int')]);
    $result = $this->builder->build($node, $this->ctx);
    expect($result)->toBeInstanceOf(ObjectType::class);
});

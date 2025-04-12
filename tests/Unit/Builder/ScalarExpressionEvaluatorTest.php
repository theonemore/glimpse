<?php

use Fw2\Glimpse\Builder\ScalarExpressionEvaluator;
use Fw2\Glimpse\Context\Context;
use PhpParser\Node\Expr;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Scalar;
use PhpParser\ParserFactory;

beforeEach(function () {
    $this->parser = (new ParserFactory)->createForHostVersion();
    $this->evaluator = new ScalarExpressionEvaluator($this->parser);
    $this->context = mock(Context::class);
});

it('evaluates scalar values', function () {
    expect($this->evaluator->evaluate(new Scalar\LNumber(42), $this->context))->toBe(42)
        ->and($this->evaluator->evaluate(new Scalar\DNumber(3.14), $this->context))->toBe(3.14)
        ->and($this->evaluator->evaluate(new Scalar\String_('hello'), $this->context))->toBe('hello');
});

it('evaluates const fetches', function () {
    $trueExpr = new Expr\ConstFetch(new PhpParser\Node\Name('true'));
    $falseExpr = new Expr\ConstFetch(new PhpParser\Node\Name('false'));
    $nullExpr = new Expr\ConstFetch(new PhpParser\Node\Name('null'));

    expect($this->evaluator->evaluate($trueExpr, $this->context))->toBeTrue()
        ->and($this->evaluator->evaluate($falseExpr, $this->context))->toBeFalse()
        ->and($this->evaluator->evaluate($nullExpr, $this->context))->toBeNull();
});

it('evaluates binary operations', function () {
    $expr = new Expr\BinaryOp\Plus(
        new Scalar\LNumber(2),
        new Scalar\LNumber(3)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(5);
});

it('evaluates ternary expression', function () {
    $expr = new Expr\Ternary(
        new Expr\ConstFetch(new PhpParser\Node\Name('true')),
        new Scalar\String_('yes'),
        new Scalar\String_('no'),
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe('yes');
});

it('evaluates simple array expression', function () {
    $expr = new Expr\Array_([
        new PhpParser\Node\Expr\ArrayItem(new Scalar\String_('one')),
        new PhpParser\Node\Expr\ArrayItem(new Scalar\String_('two')),
    ]);

    expect($this->evaluator->evaluate($expr, $this->context))->toBe(['one', 'two']);
});

it('evaluates keyed array expression', function () {
    $expr = new Expr\Array_([
        new PhpParser\Node\Expr\ArrayItem(new Scalar\String_('one'), new Scalar\String_('two')),
        new PhpParser\Node\Expr\ArrayItem(new Scalar\String_('two'), new Scalar\String_('three')),
    ]);

    expect($this->evaluator->evaluate($expr, $this->context))->toBe(['two' => 'one', 'three' => 'two']);
});


it('evaluates class::const fetch', function () {
    $ctx = mock(Context::class);
    $ctx->shouldReceive('fqcn')->with(ReflectionMethod::class)->andReturn(ReflectionMethod::class);

    $expr = new Expr\ClassConstFetch(
        new PhpParser\Node\Name(ReflectionMethod::class),
        new PhpParser\Node\Identifier('IS_PUBLIC'),
    );

    expect($this->evaluator->evaluate($expr, $ctx))->toBe(ReflectionMethod::IS_PUBLIC);
});


it('evaluates self::const fetch', function () {
    $ctx = mock(Context::class);
    $ctx->shouldReceive('getStatic')->andReturn(ReflectionMethod::class);

    $expr = new Expr\ClassConstFetch(
        new PhpParser\Node\Name('self'),
        new PhpParser\Node\Identifier('IS_PUBLIC'),
    );

    expect($this->evaluator->evaluate($expr, $ctx))->toBe(ReflectionMethod::IS_PUBLIC);
});


it('evaluates static::const fetch', function () {
    $ctx = mock(Context::class);
    $ctx->shouldReceive('getStatic')->andReturn(ReflectionMethod::class);

    $expr = new Expr\ClassConstFetch(
        new PhpParser\Node\Name('static'),
        new PhpParser\Node\Identifier('IS_PUBLIC'),
    );

    expect($this->evaluator->evaluate($expr, $ctx))->toBe(ReflectionMethod::IS_PUBLIC);
});

it('evaluates parent::const fetch', function () {
    $ctx = mock(Context::class);
    $ctx->shouldReceive('getParent')->andReturn(ReflectionMethod::class);

    $expr = new Expr\ClassConstFetch(
        new PhpParser\Node\Name('parent'),
        new PhpParser\Node\Identifier('IS_PUBLIC'),
    );

    expect($this->evaluator->evaluate($expr, $ctx))->toBe(ReflectionMethod::IS_PUBLIC);
});


it('evaluates expr::const fetch', function () {
    $ctx = mock(Context::class);
    $expr = new Expr\ClassConstFetch(
        new Scalar\String_(ReflectionMethod::class),
        new PhpParser\Node\Identifier('IS_PUBLIC'),
    );

    expect($this->evaluator->evaluate($expr, $ctx))->toBe(ReflectionMethod::IS_PUBLIC);
});


it('evaluates ::const fetch', function () {
    $ctx = mock(Context::class);

    $expr = new Expr\ClassConstFetch(
        new Scalar\String_(''),
        new PhpParser\Node\Identifier('IS_PUBLIC'),
    );

    $this->evaluator->evaluate($expr, $ctx);
})->throws(\LogicException::class, 'Can not evaluate: ::IS_PUBLIC');


it('evaluates MissingClass::const fetch', function () {
    $ctx = mock(Context::class);
    $ctx->shouldReceive('fqcn')->andReturn('MissingClass');

    $expr = new Expr\ClassConstFetch(
        new PhpParser\Node\Name('MissingClass'),
        new PhpParser\Node\Identifier('IS_PUBLIC'),
    );

    $this->evaluator->evaluate($expr, $ctx);
})->throws(\LogicException::class, 'Class not found: MissingClass');


it('evaluates class::MissingConst fetch', function () {
    $ctx = mock(Context::class);
    $ctx->shouldReceive('fqcn')->with(ReflectionMethod::class)->andReturn(ReflectionMethod::class);

    $expr = new Expr\ClassConstFetch(
        new PhpParser\Node\Name(ReflectionMethod::class),
        new PhpParser\Node\Identifier('MissingConst'),
    );
    $this->evaluator->evaluate($expr, $ctx);
})->throws(\LogicException::class, 'Constant ReflectionMethod::MissingConst is not defined');


it('evaluates unsupported const fetch', function () {
    $ctx = mock(Context::class);
    $ctx->shouldReceive('fqcn')->andReturn('MissingClass');

    $expr = new Expr\ClassConstFetch(
        new PhpParser\Node\Scalar\Int_(1),
        new PhpParser\Node\Identifier('IS_PUBLIC'),
    );

    $this->evaluator->evaluate($expr, $ctx);
})->throws(\LogicException::class, 'Unsupported fqcn expression result type: integer');


it('evaluates bitwise AND', function () {
    $expr = new Expr\BinaryOp\BitwiseAnd(
        new Scalar\LNumber(5), // 0101
        new Scalar\LNumber(3)  // 0011
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(1); // 0001
});

it('evaluates bitwise OR', function () {
    $expr = new Expr\BinaryOp\BitwiseOr(
        new Scalar\LNumber(5),  // 0101
        new Scalar\LNumber(3)   // 0011
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(7);  // 0111
});

it('evaluates bitwise XOR', function () {
    $expr = new Expr\BinaryOp\BitwiseXor(
        new Scalar\LNumber(5),  // 0101
        new Scalar\LNumber(3)   // 0011
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(6);  // 0110
});

it('evaluates shift left', function () {
    $expr = new Expr\BinaryOp\ShiftLeft(
        new Scalar\LNumber(2),  // 0010
        new Scalar\LNumber(1)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(4);  // 0100
});

it('evaluates shift right', function () {
    $expr = new Expr\BinaryOp\ShiftRight(
        new Scalar\LNumber(4),  // 0100
        new Scalar\LNumber(1)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(2);  // 0010
});

it('evaluates minus', function () {
    $expr = new Expr\BinaryOp\Minus(
        new Scalar\LNumber(5),
        new Scalar\LNumber(3)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(2);
});

it('evaluates multiplication', function () {
    $expr = new Expr\BinaryOp\Mul(
        new Scalar\LNumber(2),
        new Scalar\LNumber(3)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(6);
});

it('evaluates division', function () {
    $expr = new Expr\BinaryOp\Div(
        new Scalar\LNumber(6),
        new Scalar\LNumber(2)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(3);
});

it('evaluates modulus', function () {
    $expr = new Expr\BinaryOp\Mod(
        new Scalar\LNumber(5),
        new Scalar\LNumber(3)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(2);
});

it('evaluates concatenation', function () {
    $expr = new Expr\BinaryOp\Concat(
        new Scalar\String_('Hello'),
        new Scalar\String_(' World')
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe('Hello World');
});

it('evaluates equality', function () {
    $expr = new Expr\BinaryOp\Equal(
        new Scalar\LNumber(5),
        new Scalar\LNumber(5)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates inequality', function () {
    $expr = new Expr\BinaryOp\NotEqual(
        new Scalar\LNumber(5),
        new Scalar\LNumber(3)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates identical', function () {
    $expr = new Expr\BinaryOp\Identical(
        new Scalar\LNumber(5),
        new Scalar\LNumber(5)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates not identical', function () {
    $expr = new Expr\BinaryOp\NotIdentical(
        new Scalar\LNumber(5),
        new Scalar\LNumber(3)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates smaller than', function () {
    $expr = new Expr\BinaryOp\Smaller(
        new Scalar\LNumber(3),
        new Scalar\LNumber(5)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates smaller or equal', function () {
    $expr = new Expr\BinaryOp\SmallerOrEqual(
        new Scalar\LNumber(3),
        new Scalar\LNumber(5)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates greater than', function () {
    $expr = new Expr\BinaryOp\Greater(
        new Scalar\LNumber(7),
        new Scalar\LNumber(5)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates greater or equal', function () {
    $expr = new Expr\BinaryOp\GreaterOrEqual(
        new Scalar\LNumber(7),
        new Scalar\LNumber(5)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates boolean AND', function () {
    $expr = new Expr\BinaryOp\BooleanAnd(
        new Expr\ConstFetch(new PhpParser\Node\Name('true')),
        new Expr\ConstFetch(new PhpParser\Node\Name('false'))
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeFalse();
});

it('evaluates logical AND', function () {
    $expr = new Expr\BinaryOp\LogicalAnd(
        new Expr\ConstFetch(new PhpParser\Node\Name('true')),
        new Expr\ConstFetch(new PhpParser\Node\Name('false'))
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeFalse();
});

it('evaluates boolean OR', function () {
    $expr = new Expr\BinaryOp\BooleanOr(
        new Expr\ConstFetch(new PhpParser\Node\Name('true')),
        new Expr\ConstFetch(new PhpParser\Node\Name('false'))
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates logical OR', function () {
    $expr = new Expr\BinaryOp\LogicalOr(
        new Expr\ConstFetch(new PhpParser\Node\Name('true')),
        new Expr\ConstFetch(new PhpParser\Node\Name('false'))
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates logical XOR', function () {
    $expr = new Expr\BinaryOp\LogicalXor(
        new Expr\ConstFetch(new PhpParser\Node\Name('true')),
        new Expr\ConstFetch(new PhpParser\Node\Name('false'))
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates coalesce', function () {
    $expr = new Expr\BinaryOp\Coalesce(
        new Expr\ConstFetch(new PhpParser\Node\Name('null')),
        new Scalar\String_('fallback')
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe('fallback');
});


it('evaluates array dimension fetch', function () {
    $expr = new Expr\ArrayDimFetch(
        new Expr\Array_([new PhpParser\Node\Expr\ArrayItem(new Scalar\String_('value'))]),
        new Scalar\LNumber(0)
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe('value');
});


it('throws when accessing dimension of non-array', function () {
    $expr = new Expr\ArrayDimFetch(
        new Scalar\String_('not array'),
        new Scalar\LNumber(0)
    );

    $this->evaluator->evaluate($expr, $this->context);
})->throws(\LogicException::class, 'Trying array access to non-array');


it('throws when accessing array[] without index', function () {
    $expr = new Expr\ArrayDimFetch(
        new Expr\Array_([new PhpParser\Node\Expr\ArrayItem(new Scalar\String_('value'))]),
        null
    );

    $this->evaluator->evaluate($expr, $this->context);
})->throws(\LogicException::class, 'Cannot compute $array[] with out definition context');


it('throws when accessing missing key in array', function () {
    $expr = new Expr\ArrayDimFetch(
        new Expr\Array_([
            new PhpParser\Node\Expr\ArrayItem(
                new Scalar\String_('value'),
                new Scalar\String_('existing')
            ),
        ]),
        new Scalar\String_('missing')
    );

    $this->evaluator->evaluate($expr, $this->context);
})->throws(\LogicException::class, "Missing key 'missing'");

it('evaluates unary minus', function () {
    $expr = new Expr\UnaryMinus(new Scalar\LNumber(5));
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(-5);
});

it('evaluates unary plus', function () {
    $expr = new Expr\UnaryPlus(new Scalar\LNumber(5));
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(5);
});

it('evaluates boolean NOT', function () {
    $expr = new Expr\BooleanNot(new Expr\ConstFetch(new PhpParser\Node\Name('true')));
    expect($this->evaluator->evaluate($expr, $this->context))->toBeFalse();
});

it('evaluates bitwise NOT', function () {
    $expr = new Expr\BitwiseNot(new Scalar\LNumber(5));  // 0101
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(-6); // 1010 (two's complement)
});

it('evaluates int cast', function () {
    $expr = new Expr\Cast\Int_(new Scalar\DNumber(3.14));
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(3);
});

it('evaluates double cast', function () {
    $expr = new Expr\Cast\Double(new Scalar\LNumber(5));
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(5.0);
});

it('evaluates string cast', function () {
    $expr = new Expr\Cast\String_(new Scalar\LNumber(5));
    expect($this->evaluator->evaluate($expr, $this->context))->toBe('5');
});

it('evaluates boolean cast', function () {
    $expr = new Expr\Cast\Bool_(new Scalar\LNumber(0));
    expect($this->evaluator->evaluate($expr, $this->context))->toBeFalse();
});

it('evaluates array cast', function () {
    $expr = new Expr\Cast\Array_(new Scalar\String_('test'));
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(['test']);
});

it('evaluates unset cast', function () {
    $expr = new Expr\Cast\Unset_(new Expr\ConstFetch(new PhpParser\Node\Name('someVar')));
    expect($this->evaluator->evaluate($expr, $this->context))->toBeNull();
});

it('evaluates empty check when is not empty', function () {
    $expr = new Expr\Empty_(new Expr\ConstFetch(new PhpParser\Node\Name('PHP_EOL')));
    expect($this->evaluator->evaluate($expr, $this->context))->toBeFalse();
});

it('evaluates empty check when is empty', function () {
    $expr = new Expr\Empty_(new Expr\ConstFetch(new PhpParser\Node\Name('someCustomConstName')));
    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates match expression', function () {
    $expr = new Expr\Match_(
        new Scalar\LNumber(2),
        [
            new MatchArm([new Scalar\LNumber(1)], new Scalar\String_('one')),
            new MatchArm([new Scalar\LNumber(2)], new Scalar\String_('two')),
        ]
    );
    expect($this->evaluator->evaluate($expr, $this->context))->toBe('two');
});


it('evaluates match expression with default arm', function () {
    $expr = new Expr\Match_(
        cond: new Scalar\LNumber(42),
        arms: [
            new MatchArm(
                conds: null,
                body: new Scalar\String_('default')
            )
        ]
    );

    expect($this->evaluator->evaluate($expr, $this->context))->toBe('default');
});


it('throws when match expression has no matching arm and no default', function () {
    $expr = new Expr\Match_(
        cond: new Scalar\LNumber(1),
        arms: [
            new MatchArm(
                conds: [new Scalar\LNumber(2)],
                body: new Scalar\String_('no match')
            )
        ]
    );

    $this->evaluator->evaluate($expr, $this->context);
})->throws(\LogicException::class, 'match: does not contain default value');

it('evaluates isset check', function () {
    $expr = new Expr\Isset_([new Expr\ConstFetch(new PhpParser\Node\Name('someVar'))]);
    expect($this->evaluator->evaluate($expr, $this->context))->toBeFalse();
});

it('evaluates isset to true when all vars are set', function () {
    $array = new Expr\Array_([
        new PhpParser\Node\Expr\ArrayItem(new Scalar\String_('value'), new Scalar\String_('key'))
    ]);

    $fetch = new Expr\ArrayDimFetch($array, new Scalar\String_('key'));
    $expr = new Expr\Isset_([$fetch]);

    expect($this->evaluator->evaluate($expr, $this->context))->toBeTrue();
});

it('evaluates eval expression', function () {
    $expr = new Expr\Eval_(new Scalar\String_('42'));
    expect($this->evaluator->evaluate($expr, $this->context))->toBe(42);
});

it('throws when eval code does not contain expression', function () {
    $expr = new Expr\Eval_(
        new Scalar\String_('class A {}')
    );

    $this->evaluator->evaluate($expr, $this->context);
})->throws(\LogicException::class, 'eval() should contain expression');

it('throws if eval() is not a string', function () {
    $expr = new Expr\Eval_(new Scalar\LNumber(123));

    $this->evaluator->evaluate($expr, $this->context);
})->throws(\LogicException::class, 'eval() should be string');

it('evaluates nullsafe property fetch', function () {
    $expr = mock(Expr\NullsafePropertyFetch::class);
    $this->evaluator->evaluate($expr, $this->context);
})->throws(\LogicException::class, 'Property fetch is not supported');


it('evaluates property fetch', function () {
    $expr = mock(Expr\PropertyFetch::class);
    $this->evaluator->evaluate($expr, $this->context);
})->throws(\LogicException::class, 'Property fetch is not supported');

it('evaluates method call', function () {
    $expr = mock(Expr\MethodCall::class);
    $this->evaluator->evaluate($expr, $this->context);
})->throws(\LogicException::class, 'Method call is not supported');


it('evaluates nullsafe method call', function () {
    $expr = mock(Expr\NullsafeMethodCall::class);
    $this->evaluator->evaluate($expr, $this->context);
})->throws(\LogicException::class, 'Method call is not supported');


it('evaluates object cast', function () {
    $expr = mock(Expr\Cast\Object_::class);
    $this->evaluator->evaluate($expr, $this->context);
})->throws(\LogicException::class, '(object) cast is not supported');

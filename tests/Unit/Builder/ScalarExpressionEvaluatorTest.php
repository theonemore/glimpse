<?php

use Fw2\Mentalist\Builder\Context\Context;
use Fw2\Mentalist\Builder\ScalarExpressionEvaluator;
use Mockery as m;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\ParserFactory;

beforeEach(function () {
    $this->parser = (new ParserFactory)->createForHostVersion();
    $this->evaluator = new ScalarExpressionEvaluator($this->parser);
    $this->context = m::mock(Context::class);
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

it('throws exception for unknown constant', function () {
    $this->evaluator->evaluate(
        new Expr\ConstFetch(new PhpParser\Node\Name('SOME_UNKNOWN_CONST')),
        $this->context
    );
})->throws(LogicException::class, 'Unknown constant: SOME_UNKNOWN_CONST');

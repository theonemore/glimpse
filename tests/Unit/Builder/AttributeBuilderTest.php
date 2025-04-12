<?php

use Fw2\Glimpse\Builder\AttributeBuilder;
use Fw2\Glimpse\Builder\ScalarExpressionEvaluator;
use Fw2\Glimpse\Context\Context;
use Fw2\Glimpse\Entity\Attribute as BuilderAttribute;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute as ParserAttribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Name as NodeName;
use PhpParser\Node\Scalar\String_;

beforeEach(function () {
    $this->evaluator = mock(ScalarExpressionEvaluator::class);
    $this->builder = new AttributeBuilder($this->evaluator);
    $this->ctx = new Context();

    $this->ctxMock = mock(Context::class);
    $this->ctxMock->shouldReceive('fqcn')->andReturnUsing(fn($name) => "Fully\\Qualified\\{$name}");
});

it('builds empty attributes when no groups provided', function () {
    $result = $this->builder->build([], $this->ctx);
    expect($result)->toBe([]);
});

it('builds single attribute without arguments', function () {
    $attrGroup = new AttributeGroup([
        new ParserAttribute(new Name('TestAttribute'), [])
    ]);

    $result = $this->builder->build([$attrGroup], $this->ctxMock);

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(BuilderAttribute::class)
        ->and($result[0]->fqcn)->toBe('Fully\\Qualified\\TestAttribute')
        ->and($result[0]->arguments)->toBe([]);
});

it('builds multiple attributes with arguments', function () {
    $stringArg = new Arg(new String_('test'));
    $boolArg = new Arg(new ConstFetch(new NodeName('true')));

    $attrGroup = new AttributeGroup([
        new ParserAttribute(new Name('FirstAttribute'), [$stringArg]),
        new ParserAttribute(new Name('SecondAttribute'), [$boolArg])
    ]);

    $this->evaluator->shouldReceive('evaluate')
        ->with($stringArg->value, $this->ctxMock)
        ->andReturn('evaluated string');

    $this->evaluator->shouldReceive('evaluate')
        ->with($boolArg->value, $this->ctxMock)
        ->andReturn(true);

    $result = $this->builder->build([$attrGroup], $this->ctxMock);

    expect($result)->toHaveCount(2)
        ->and($result[0]->fqcn)->toBe('Fully\\Qualified\\FirstAttribute')
        ->and($result[0]->arguments)->toBe(['evaluated string'])
        ->and($result[1]->fqcn)->toBe('Fully\\Qualified\\SecondAttribute')
        ->and($result[1]->arguments)->toBe([true]);
});

it('builds attributes from multiple groups', function () {
    $group1 = new AttributeGroup([
        new ParserAttribute(new Name('FirstAttr'), [])
    ]);

    $group2 = new AttributeGroup([
        new ParserAttribute(new Name('SecondAttr'), []),
        new ParserAttribute(new Name('ThirdAttr'), [])
    ]);

    $result = $this->builder->build([$group1, $group2], $this->ctxMock);

    expect($result)->toHaveCount(3)
        ->and($result[0]->fqcn)->toBe('Fully\\Qualified\\FirstAttr')
        ->and($result[1]->fqcn)->toBe('Fully\\Qualified\\SecondAttr')
        ->and($result[2]->fqcn)->toBe('Fully\\Qualified\\ThirdAttr');
});

it('evaluates all arguments for each attribute', function () {
    $arg1 = new Arg(new String_('arg1'));
    $arg2 = new Arg(new String_('arg2'));

    $attrGroup = new AttributeGroup([
        new ParserAttribute(new Name('TestAttr'), [$arg1, $arg2])
    ]);

    $this->evaluator->shouldReceive('evaluate')
        ->with($arg1->value, $this->ctxMock)
        ->andReturn('first');

    $this->evaluator->shouldReceive('evaluate')
        ->with($arg2->value, $this->ctxMock)
        ->andReturn('second');

    $result = $this->builder->build([$attrGroup], $this->ctxMock);

    expect($result[0]->arguments)->toBe(['first', 'second']);
});

it('uses context to resolve FQCN for attribute names', function () {
    $customCtx = mock(Context::class);
    $customCtx->shouldReceive('fqcn')
        ->with('TestAttribute')
        ->andReturn('Custom\\Namespace\\TestAttribute');

    $attrGroup = new AttributeGroup([
        new ParserAttribute(new Name('TestAttribute'), [])
    ]);

    $result = $this->builder->build([$attrGroup], $customCtx);

    expect($result[0]->fqcn)->toBe('Custom\\Namespace\\TestAttribute');
});

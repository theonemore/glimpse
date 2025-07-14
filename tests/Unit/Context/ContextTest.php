<?php


use Fw2\Glimpse\Context;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;

it('resolves fully qualified class name from use statements', function () {
    $context = new Context();
    $context->addAlias('ClassName', 'Vendor\\Package\\ClassName');
    expect($context->resolve('ClassName'))->toBe('Vendor\\Package\\ClassName');
});

it('resolves fqcn from namespace when no use match', function () {
    $context = new Context('App\\Http');

    expect($context->resolve('Controller'))->toBe('App\\Http\\Controller');
});


it('returns alias directly if already fully qualified', function () {
    $context = new Context();
    expect($context->resolve('\\Foo\\Bar'))->toBe('\\Foo\\Bar');
});

it('can set and get parent context', function () {
    $context = new Context();
    $context->setParent('BaseClass');

    expect($context->getParent())->toBe('BaseClass');
});


it('parses Namespace_ with both Use_ and GroupUse statements', function () {
    $namespace = new Namespace_(new Name('My\\Ns'), [
        new Use_([
            new UseItem(new Name('Lib\\SingleClass')),
        ]),
        new GroupUse(new Name('Lib\\Group'), [
            new UseItem(new Name('One')),
            new UseItem(new Name('Two')),
        ]),
    ]);

    $context = new Context('My\\Ns');
    $context->addAlias('SingleClass', 'Lib\\SingleClass');
    $context->addAlias('One', 'Lib\\Group\\One');
    $context->addAlias('Two', 'Lib\\Group\\Two');

    expect($context->resolve('SingleClass'))->toBe('Lib\\SingleClass')
        ->and($context->resolve('One'))->toBe('Lib\\Group\\One')
        ->and($context->resolve('Two'))->toBe('Lib\\Group\\Two');
});

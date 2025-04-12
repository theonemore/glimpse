<?php

use Fw2\Glimpse\Context\Context;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\UseItem;

it('resolves fully qualified class name from use statements', function () {
    $use = new Use_([new UseItem(new Name('Vendor\\Package\\ClassName'))]);
    $context = new Context();
    $context->addUse($use);

    expect($context->fqcn('ClassName'))->toBe('Vendor\\Package\\ClassName');
});

it('resolves fqcn from namespace when no use match', function () {
    $ns = new Namespace_(new Name('App\\Http'));
    $context = new Context($ns);

    expect($context->fqcn('Controller'))->toBe('App\\Http\\Controller');
});

it('resolves alias from grouped use', function () {
    $group = new GroupUse(new Name('My\\Lib'), [
        new UseItem(new Name('Tools')),
    ]);
    $context = new Context();
    $context->addGroup($group);

    expect($context->fqcn('Tools'))->toBe('My\\Lib\\Tools');
});

it('returns alias directly if already fully qualified', function () {
    $context = new Context();
    expect($context->fqcn('\\Foo\\Bar'))->toBe('\\Foo\\Bar');
});

it('splits string by last backslash', function () {
    $context = new Context();
    expect($context->splitByLastBackslash('A\\B\\C'))->toBe(['A\\B', 'C'])
        ->and($context->splitByLastBackslash('Simple'))->toBe(['', 'Simple']);
});

it('creates phpdoc context', function () {
    $context = new Context(new Namespace_(new Name('App')));
    $doc = $context->toPhpDoc();

    expect($doc->getNamespace())->toBe('App');
});

it('can clone and set static context', function () {
    $context = new Context();
    $newContext = $context->for('My\\Class');

    expect($newContext->getStatic())->toBe('My\\Class')
        ->and($context->getStatic())->toBeNull();
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

    $context = new Context($namespace);

    expect($context->fqcn('SingleClass'))->toBe('Lib\\SingleClass')
        ->and($context->fqcn('One'))->toBe('Lib\\Group\\One')
        ->and($context->fqcn('Two'))->toBe('Lib\\Group\\Two');
});

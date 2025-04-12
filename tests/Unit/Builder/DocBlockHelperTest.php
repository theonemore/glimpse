<?php

use Fw2\Glimpse\Builder\DocBlockHelper;
use Fw2\Glimpse\Context\Context;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\String_;

function createHelper(): DocBlockHelper
{
    return new DocBlockHelper(DocBlockFactory::createInstance());
}

it('returns null when comment is null', function () {
    $helper = createHelper();

    $doc = $helper->create(null, new Context());

    expect($doc)->toBeNull();
});

it('parses valid docblock', function () {
    $helper = createHelper();
    $doc = $helper->create(
        <<<PHP
        /**
         * Summary line.
         *
         * Description text here.
         *
         * @param string \$name
         * @param int \$age
         * @return object
         * @var string Description of var
         */
        PHP,
        new Context()
    );

    expect($doc)->not->toBeNull()
        ->and($helper->getSummary($doc))->toBe('Summary line.')
        ->and($helper->getDescription($doc))->toContain('Description text here.');

    $paramTypes = $helper->getParamTypes($doc);
    expect($paramTypes)->toHaveKeys(['name', 'age'])
        ->and($paramTypes['name'])->toBeInstanceOf(String_::class)
        ->and($paramTypes['age'])->toBeInstanceOf(Integer::class);

    $returnType = $helper->getReturnType($doc);
    expect($returnType)->toBeInstanceOf(Object_::class);

    $varType = $helper->getVarType($doc);
    expect($varType)->toBeInstanceOf(String_::class);

    $varDescription = $helper->getVarDescription($doc);
    expect($varDescription)->toBeInstanceOf(Description::class)
        ->and($varDescription->__toString())->toContain('Description of var');
});

it('returns empty results when no tags exist', function () {
    $helper = createHelper();
    $doc = $helper->create(
        <<<PHP
        /** Just a comment without tags. */
        PHP,
        new Context()
    );

    expect($helper->getParamTypes($doc))->toBe([])
        ->and($helper->getReturnType($doc))->toBeNull()
        ->and($helper->getVarType($doc))->toBeNull()
        ->and($helper->getVarDescription($doc))->toBeNull();
});


it('returns null when an exception is thrown during DocBlock creation', function () {

    $factoryMock = $this->createMock(DocBlockFactoryInterface::class);

    $factoryMock->method('create')
        ->will($this->throwException(new \Exception('DocBlock creation failed')));

    $helper = new DocBlockHelper($factoryMock);

    $doc = $helper->create('invalid doc comment', new Context());
    expect($doc)->toBeNull();
});


it('returns an empty array when no DocBlock is provided', function () {
    $helper = createHelper();

    $result = $helper->getParamTypes(null);

    expect($result)->toBe([]);
});

<?php


use Fw2\Glimpse\Context;
use Fw2\Glimpse\PhpDoc\DocBlockHelper;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

function createHelper(): DocBlockHelper
{
    $cfg = new ParserConfig([]);
    $expressionParser = new ConstExprParser($cfg);
    return new DocBlockHelper(
        new  PhpDocParser(
            $cfg,
            new TypeParser($cfg, $expressionParser),
            $expressionParser,
        ),
        new Lexer($cfg),
    );
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
        PHP
    );

    expect($doc)->not->toBeNull()
        ->and($helper->getSummary($doc))->toBe('Summary line.')
        ->and($helper->getDescription($doc))->toContain('Description text here.');

    $paramTypes = $helper->getParamTypes($doc);

    expect($paramTypes)->toHaveKeys(['$name', '$age'])
        ->and($paramTypes['$name'])->toBeInstanceOf(IdentifierTypeNode::class)
        ->and($paramTypes['$name']->name)->toEqual('string')
        ->and($paramTypes['$age'])->toBeInstanceOf(IdentifierTypeNode::class)
        ->and($paramTypes['$age']->name)->toEqual('int');

    $returnType = $helper->getReturnType($doc);
    expect($returnType)
        ->toBeInstanceOf(IdentifierTypeNode::class)
        ->and($returnType->name)->toEqual('object');

    $varType = $helper->getVarType($doc);
    expect($varType)->toBeInstanceOf(IdentifierTypeNode::class)
        ->and($varType->name)->toEqual('string')
    ;

    $varDescription = $helper->getVarDescription($doc);
    expect($varDescription)
        ->toBeString()
        ->toContain('Description of var');
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
    $docParserMock = $this->createMock(PhpDocParser::class);
    $lexerMock = $this->createMock(Lexer::class);

    $docParserMock->method('parse')
        ->will($this->throwException(new Exception('DocBlock creation failed')));

    $helper = new DocBlockHelper($docParserMock, $lexerMock);

    $doc = $helper->create('invalid doc comment', new Context());
    expect($doc)->toBeNull();
});


it('returns an empty array when no DocBlock is provided', function () {
    $helper = createHelper();

    $result = $helper->getParamTypes(null);

    expect($result)->toBe([]);
});

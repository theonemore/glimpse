<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Ast;

use Fw2\Glimpse\Providers\ParserProvider;
use PhpParser\Node\Stmt;
use ReflectionClass;
use ReflectionException;

class AstResolver
{
    /**
     * @var array<class-string, array<int, Stmt>>
     */
    private array $parsed = [];

    private ParserProvider $parserProvider;

    public function __construct(ParserProvider $parserProvider)
    {
        $this->parserProvider = $parserProvider;
    }

    /**
     * @throws ReflectionException
     * @return array<int, Stmt>
     */
    public function resolve(string $fqcn): array
    {
        if (!isset($this->parsed[$fqcn])) {
            $ref = (new ReflectionClass($fqcn));

            if ($ref->isInternal()) {
                throw new \RuntimeException(sprintf('Resolving class can not be internal. %s given', $fqcn));
            }

            $file = $ref->getFileName();

            $this->parsed[$fqcn] = $this->parserProvider->get()->parse(file_get_contents($file));
        }

        return $this->parsed[$fqcn];
    }
}

<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Ast;

use Composer\Autoload\ClassLoader;
use LogicException;
use PhpParser\Node\Stmt;
use PhpParser\Parser;

class AstResolver
{
    /**
     * @var Stmt[][]
     */
    private array $parsed = [];

    public function __construct(
        private readonly Parser $parser,
        /** @var ClassLoader[] $loaders */
        private readonly array $loaders
    ) {
    }

    /**
     * @return Stmt[]
     * @throws LogicException
     */
    public function resolve(string $fqcn): array
    {
        if (isset($this->parsed[$fqcn])) {
            return  $this->parsed[$fqcn];
        }

        foreach ($this->loaders as $loader) {
            foreach ($loader->getPrefixesPsr4() as $prefix => $paths) {
                if (str_starts_with($fqcn, $prefix)) {
                    $fqcn = str_replace($prefix, '', $fqcn);
                    foreach ($paths as $path) {
                        $path = sprintf('%s%s%s.php', $path, DIRECTORY_SEPARATOR, $fqcn);
                        if (file_exists($path)) {
                            $this->parsed[$fqcn] = $this->parser->parse(file_get_contents($path));
                        }
                    }
                }
            }
        }

        return $this->parsed[$fqcn] ?? throw new LogicException(
            sprintf(
                'Source code for class "%s" is not found',
                $fqcn
            )
        );
    }
}

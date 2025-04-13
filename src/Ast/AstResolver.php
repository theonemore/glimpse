<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Ast;

use Composer\Autoload\ClassLoader;
use Fw2\Glimpse\Providers\ParserProvider;
use LogicException;
use PhpParser\Node\Stmt;

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
     * @return array<int, Stmt>
     * @throws LogicException
     */
    public function resolve(string $fqcn): array
    {
        if (!isset($this->parsed[$fqcn])) {
            $loaders = ClassLoader::getRegisteredLoaders();
            foreach ($loaders as $loader) {
                foreach ($loader->getPrefixesPsr4() as $prefix => $paths) {
                    if (str_starts_with($fqcn, $prefix)) {
                        $fqcn = str_replace($prefix, '', $fqcn);
                        foreach ($paths as $path) {
                            $path = sprintf('%s%s%s.php', $path, DIRECTORY_SEPARATOR, $fqcn);
                            if (file_exists($path)) {
                                $this->parsed[$fqcn] = $this->parserProvider->get()->parse(file_get_contents($path));
                            }
                        }
                    }
                }
            }
        }

        return $this->parsed[$fqcn] ?? throw new LogicException(sprintf('Source code for class "%s" is not found', $fqcn));
    }
}

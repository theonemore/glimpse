<?php

namespace Fw2\Mentalist\Providers;

use PhpParser\Parser;
use PhpParser\ParserFactory;

class ParserProvider
{
    private ?Parser $parser = null;

    public function __construct(
        readonly private ParserFactory $parserFactory,
    ) {
    }

    public function get(): Parser
    {
        return $this->parser ?? $this->parser = $this->parserFactory->createForHostVersion();
    }
}

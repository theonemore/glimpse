<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Providers;

use Fw2\Glimpse\Builder\ScalarExpressionEvaluator;

class EvaluatorProvider
{
    private ?ScalarExpressionEvaluator $evaluator = null;

    public function __construct(
        readonly private ParserProvider $parserProvider,
    ) {
    }

    public function get(): ScalarExpressionEvaluator
    {
        return $this->evaluator ?? $this->evaluator = new ScalarExpressionEvaluator($this->parserProvider->get());
    }
}

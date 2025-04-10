<?php

namespace Fw2\Mentalist\Providers;

use Fw2\Mentalist\Builder\ScalarExpressionEvaluator;

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

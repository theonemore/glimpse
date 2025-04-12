<?php

declare(strict_types=1);

namespace Fw2\Glimpse\Builder;

use Fw2\Glimpse\Context\Context;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Parser;

class ScalarExpressionEvaluator
{
    private Parser $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function evaluate(Expr $expr, Context $context): mixed
    {
        return match (true) {
            $expr instanceof Scalar\LNumber,
                $expr instanceof Scalar\DNumber,
                $expr instanceof Scalar\String_ => $expr->value,
            $expr instanceof Expr\ConstFetch => match (strtolower($expr->name->toString())) {
                'true' => true,
                'false' => false,
                'null' => null,
                default => defined($expr->name->name) ? constant($expr->name->name) : null,
            },
            $expr instanceof Expr\BinaryOp\BitwiseAnd =>
                $this->evaluate($expr->left, $context) & $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\BitwiseOr =>
                $this->evaluate($expr->left, $context) | $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\BitwiseXor =>
                $this->evaluate($expr->left, $context) ^ $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\ShiftLeft =>
                $this->evaluate($expr->left, $context) << $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\ShiftRight =>
                $this->evaluate($expr->left, $context) >> $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\Plus =>
                $this->evaluate($expr->left, $context) + $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\Minus =>
                $this->evaluate($expr->left, $context) - $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\Mul =>
                $this->evaluate($expr->left, $context) * $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\Div =>
                $this->evaluate($expr->left, $context) / $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\Mod =>
                $this->evaluate($expr->left, $context) % $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\Concat =>
                $this->evaluate($expr->left, $context) . $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\Equal =>
                $this->evaluate($expr->left, $context) == $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\NotEqual =>
                $this->evaluate($expr->left, $context) != $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\Identical =>
                $this->evaluate($expr->left, $context) === $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\NotIdentical =>
                $this->evaluate($expr->left, $context) !== $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\Smaller =>
                $this->evaluate($expr->left, $context) < $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\SmallerOrEqual =>
                $this->evaluate($expr->left, $context) <= $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\Greater =>
                $this->evaluate($expr->left, $context) > $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\GreaterOrEqual =>
                $this->evaluate($expr->left, $context) >= $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\BooleanAnd =>
                $this->evaluate($expr->left, $context) && $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\LogicalAnd =>
                $this->evaluate($expr->left, $context) and $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\BooleanOr =>
                $this->evaluate($expr->left, $context) || $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\LogicalOr =>
                $this->evaluate($expr->left, $context) or $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\LogicalXor =>
                $this->evaluate($expr->left, $context) xor $this->evaluate($expr->right, $context),
            $expr instanceof Expr\BinaryOp\Coalesce =>
                $this->evaluate($expr->left, $context) ?? $this->evaluate($expr->right, $context),
            $expr instanceof Expr\Ternary => $this->evaluate($expr->cond, $context)
                ? $this->evaluate($expr->if ?? $expr->cond, $context)
                : $this->evaluate($expr->else, $context),
            $expr instanceof ClassConstFetch => $this->evalClassConst($expr, $context),
            $expr instanceof Array_ => $this->evalArray($expr, $context),
            $expr instanceof Expr\ArrayDimFetch => $this->evalArrayDimFetch($expr, $context),
            $expr instanceof Expr\UnaryMinus => -$this->evaluate($expr->expr, $context),
            $expr instanceof Expr\UnaryPlus => +$this->evaluate($expr->expr, $context),
            $expr instanceof Expr\BooleanNot => !$this->evaluate($expr->expr, $context),
            $expr instanceof Expr\BitwiseNot => ~$this->evaluate($expr->expr, $context),
            $expr instanceof Expr\Cast\Int_ => (int)$this->evaluate($expr->expr, $context),
            $expr instanceof Expr\Cast\Double => (float)$this->evaluate($expr->expr, $context),
            $expr instanceof Expr\Cast\String_ => (string)$this->evaluate($expr->expr, $context),
            $expr instanceof Expr\Cast\Bool_ => (bool)$this->evaluate($expr->expr, $context),
            $expr instanceof Expr\Cast\Array_ => (array)$this->evaluate($expr->expr, $context),
            $expr instanceof Expr\Cast\Unset_ => null,
            $expr instanceof Expr\Empty_ => empty($this->evaluate($expr->expr, $context)),
            $expr instanceof Expr\Match_ => $this->evalMatch($expr, $context),
            $expr instanceof Expr\Isset_ => $this->evalIsset($expr, $context),
            $expr instanceof Expr\Eval_ => $this->evalEval($expr, $context),

            $expr instanceof Expr\PropertyFetch,
                $expr instanceof Expr\NullsafePropertyFetch => throw new \LogicException(
                'Property fetch is not supported'
            ),
            $expr instanceof Expr\MethodCall,
                $expr instanceof Expr\NullsafeMethodCall => throw new \LogicException('Method call is not supported'),
            $expr instanceof Expr\Cast\Object_ => throw new \LogicException('(object) cast is not supported'),

            default => throw new \LogicException('Can not compute: ' . $expr::class),
        };
    }

    /**
     * @param Array_ $array
     * @param Context $context
     * @return array<int|string, mixed>
     */
    private function evalArray(Array_ $array, Context $context): array
    {
        $result = [];
        foreach ($array->items as $item) {
            $key = $item->key !== null ? $this->evaluate($item->key, $context) : null;
            $value = $this->evaluate($item->value, $context);
            if ($key !== null) {
                $result[$key] = $value;
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    private function evalClassConst(ClassConstFetch $expr, Context $context): mixed
    {
        $className = $expr->class;
        $constName = $expr->name->name;
        $fqcn = match (true) {
            $className instanceof Node\Name => match (strtolower($className->name)) {
                'static', 'self' => $context->getStatic()
                    ?? throw new \LogicException("static/self used without context"),
                'parent' => $context->getParent(),
                default => $context->fqcn($className->name),
            },
            $className instanceof Expr => $this->evaluate($className, $context),
        };

        if (!$fqcn) {
            throw new \LogicException("Can not evaluate: ::$constName");
        }

        if (!is_string($fqcn)) {
            throw new \LogicException("Unsupported fqcn expression result type: " . gettype($fqcn));
        }

        if (!class_exists($fqcn)) {
            throw new \LogicException("Class not found: $fqcn");
        }

        if (!defined("$fqcn::$constName")) {
            throw new \LogicException("Constant $fqcn::$constName is not defined");
        }

        return constant("$fqcn::$constName");
    }

    private function evalArrayDimFetch(Expr\ArrayDimFetch $expr, Context $context): mixed
    {
        $array = $this->evaluate($expr->var, $context);

        if (!is_array($array)) {
            throw new \LogicException('Trying array access to non-array');
        }

        if ($expr->dim === null) {
            throw new \LogicException('Cannot compute $array[] with out definition context');
        }

        $key = $this->evaluate($expr->dim, $context);

        if (!array_key_exists($key, $array)) {
            throw new \LogicException("Missing key '$key'");
        }

        return $array[$key];
    }

    private function evalIsset(Expr\Isset_ $expr, Context $context): bool
    {
        foreach ($expr->vars as $var) {
            $value = $this->evaluate($var, $context);

            if (!isset($value)) {
                return false;
            }
        }

        return true;
    }

    private function evalEval(Expr\Eval_ $expr, Context $context): mixed
    {
        $code = $this->evaluate($expr->expr, $context);

        if (!is_string($code)) {
            throw new \LogicException('eval() should be string');
        }

        // Безопасная эмуляция eval (ТОЛЬКО скалярные выражения)
        $subAst = $this->parser->parse('<?php ' . $code . ';');
        $stmt = $subAst[0] ?? null;

        if (!$stmt instanceof Stmt\Expression) {
            throw new \LogicException('eval() should contain expression');
        }

        return $this->evaluate($stmt->expr, $context);
    }

    private function evalMatch(Expr\Match_ $expr, Context $context): mixed
    {
        $value = $this->evaluate($expr->cond, $context);

        foreach ($expr->arms as $arm) {
            if ($arm->conds === null) {
                return $this->evaluate($arm->body, $context);
            }

            foreach ($arm->conds as $cond) {
                if ($this->evaluate($cond, $context) === $value) {
                    return $this->evaluate($arm->body, $context);
                }
            }
        }

        throw new \LogicException('match: does not contain default value');
    }
}

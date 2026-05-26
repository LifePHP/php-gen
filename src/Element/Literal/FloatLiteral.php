<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Literal;

use LifePhp\PhpGen\Element\Type\ScalarType;
use Override;

class FloatLiteral extends Literal
{
    public function __construct(
        private readonly float $value,
    ) {
    }

    #[Override]
    public function getType(): ScalarType
    {
        return ScalarType::Float;
    }

    #[Override]
    public function render(): string
    {
        $str = (string) $this->value;

        return str_contains($str, '.') || str_contains($str, 'E') ? $str : $str . '.0';
    }
}

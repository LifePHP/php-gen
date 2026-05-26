<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Literal;

use LifePhp\PhpGen\Element\Type\ArrayKeyType;
use LifePhp\PhpGen\Element\Type\ScalarType;
use Override;

class IntLiteral extends Literal implements ArrayKeyLiteral
{
    public function __construct(
        private readonly int $value,
    ) {
    }

    #[Override]
    public function getType(): ScalarType|ArrayKeyType
    {
        return ScalarType::Int;
    }

    #[Override]
    public function getValue(): int
    {
        return $this->value;
    }

    #[Override]
    public function render(): string
    {
        return (string) $this->value;
    }
}

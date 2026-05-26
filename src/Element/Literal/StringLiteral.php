<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Literal;

use LifePhp\PhpGen\Element\Type\ArrayKeyType;
use LifePhp\PhpGen\Element\Type\ScalarType;
use Override;

class StringLiteral extends Literal implements ArrayKeyLiteral
{
    public function __construct(
        private readonly string $value,
    ) {
    }

    #[Override]
    public function getType(): ScalarType|ArrayKeyType
    {
        return ScalarType::String;
    }

    #[Override]
    public function getValue(): string
    {
        return $this->value;
    }

    #[Override]
    public function render(): string
    {
        return sprintf("'%s'", addslashes($this->value));
    }
}

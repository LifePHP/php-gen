<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Literal;

use LifePhp\PhpGen\Element\Type\ScalarType;
use Override;

class BoolLiteral extends Literal
{
    public function __construct(
        private readonly bool $value,
    ) {
    }

    #[Override]
    public function getType(): ScalarType
    {
        return $this->value ? ScalarType::True : ScalarType::False;
    }

    #[Override]
    public function render(): string
    {
        return $this->value ? 'true' : 'false';
    }
}

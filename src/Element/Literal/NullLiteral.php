<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Literal;

use LifePhp\PhpGen\Element\Type\ScalarType;
use Override;

class NullLiteral extends Literal
{
    #[Override]
    public function getType(): ScalarType
    {
        return ScalarType::Null;
    }

    #[Override]
    public function render(): string
    {
        return 'null';
    }
}

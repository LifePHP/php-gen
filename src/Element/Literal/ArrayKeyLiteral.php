<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Literal;

interface ArrayKeyLiteral extends LiteralInterface
{
    public function getValue(): int|string;
}

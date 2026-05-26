<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Literal;

use LifePhp\PhpGen\Element\ElementInterface;
use LifePhp\PhpGen\Element\Type\TypeInterface;

interface LiteralInterface extends ElementInterface
{
    public function getType(): TypeInterface;
}

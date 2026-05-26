<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Type;

use Override;

enum ConstantType: string implements TypeInterface
{
    case String = 'string';
    case Int    = 'int';
    case Float  = 'float';
    case Bool   = 'bool';
    case Null   = 'null';
    case Array  = 'array';

    #[Override]
    public function getUses(): array
    {
        return [];
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return $this->value;
    }

    #[Override]
    public function render(): string
    {
        return $this->value;
    }
}

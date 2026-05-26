<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Type;

use Override;

enum ArrayKeyType: string implements TypeInterface
{
    case Int = 'int';
    case String = 'string';
    case Both = 'int|string';

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

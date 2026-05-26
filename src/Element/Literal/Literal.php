<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Literal;

use Override;

abstract class Literal implements LiteralInterface
{
    #[Override]
    public function getUses(): array
    {
        return [];
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return $this->getType()->getDocCommentPart();
    }
}

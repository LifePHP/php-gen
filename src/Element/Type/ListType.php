<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Type;

use Override;

readonly class ListType implements TypeInterface
{
    public function __construct(
        private TypeInterface $valueType,
    ) {
    }

    #[Override]
    public function getUses(): array
    {
        return $this->valueType->getUses();
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return sprintf(
            'list<%s>',
            $this->valueType->getDocCommentPart(),
        );
    }

    #[Override]
    public function render(): string
    {
        return 'array';
    }
}

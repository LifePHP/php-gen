<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Type;

use Override;

readonly class ArrayType implements TypeInterface
{
    public function __construct(
        private TypeInterface $valueType,
        private ArrayKeyType $keyType,
    ) {
    }

    #[Override]
    public function getUses(): array
    {
        return [
            ...$this->valueType->getUses(),
            ...$this->keyType->getUses(),
        ];
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return sprintf(
            "array<\n    %s,\n    %s\n>",
            $this->keyType->getDocCommentPart(),
            $this->valueType->getDocCommentPart(),
        );
    }

    #[Override]
    public function render(): string
    {
        return 'array';
    }
}

<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Literal\LiteralInterface;
use LifePhp\PhpGen\Element\Type\ConstantType;
use Override;

class ClassConstant implements ElementInterface
{
    public function __construct(
        private readonly Visibility $visibility,
        private readonly ConstantType $type,
        private readonly string $name,
        private readonly LiteralInterface $value,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    #[Override]
    public function getUses(): array
    {
        return [];
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return '';
    }

    #[Override]
    public function render(): string
    {
        return sprintf(
            '%s const %s %s = %s',
            $this->visibility->value,
            $this->type->render(),
            $this->name,
            $this->value->render(),
        );
    }
}

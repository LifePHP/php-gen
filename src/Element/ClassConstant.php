<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Literal\LiteralInterface;
use LifePhp\PhpGen\Element\Traits\HasAttributes;
use LifePhp\PhpGen\Element\Type\ConstantType;
use Override;

class ClassConstant implements ElementInterface
{
    use HasAttributes;

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
        return $this->collectAttributeUses();
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return '';
    }

    #[Override]
    public function render(): string
    {
        $rendered = sprintf(
            '%s const %s %s = %s',
            $this->visibility->value,
            $this->type->render(),
            $this->name,
            $this->value->render(),
        );

        $attrBlock = $this->renderAttributes();

        return $attrBlock !== '' ? $attrBlock . "\n" . $rendered : $rendered;
    }
}

<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Type;

use Override;

enum ScalarType: string implements TypeInterface
{
    case String = 'string';
    case Int = 'int';
    case Float = 'float';
    case Bool = 'bool';
    case Null = 'null';
    case True = 'true';
    case False = 'false';
    case Void = 'void';
    case Mixed = 'mixed';
    case Never = 'never';
    case Callable = 'callable';
    case Object = 'object';
    case Self = 'self';
    case Static = 'static';
    case Parent = 'parent';

    #[Override]
    public function getUses(): array
    {
        return [];
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return $this->render();
    }

    #[Override]
    public function render(): string
    {
        return $this->value;
    }
}

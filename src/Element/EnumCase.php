<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Literal\IntLiteral;
use LifePhp\PhpGen\Element\Literal\StringLiteral;
use Override;

class EnumCase implements ElementInterface
{
    private function __construct(
        private readonly string $name,
        private readonly StringLiteral|IntLiteral|null $value,
    ) {
    }

    public static function pure(string $name): self
    {
        return new self($name, null);
    }

    public static function backed(string $name, StringLiteral|IntLiteral $value): self
    {
        return new self($name, $value);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): StringLiteral|IntLiteral|null
    {
        return $this->value;
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
        if ($this->value === null) {
            return 'case ' . $this->name . ';';
        }

        return 'case ' . $this->name . ' = ' . $this->value->render() . ';';
    }
}

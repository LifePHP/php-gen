<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Literal\LiteralInterface;
use Override;

class Variable implements ElementInterface
{
    private LiteralInterface|Variable|null $value = null;

    public function __construct(
        private readonly string $name,
    ) {
    }

    public function setValue(LiteralInterface|Variable $value): self
    {
        $this->value = $value;

        return $this;
    }

    #[Override]
    public function getUses(): array
    {
        if ($this->value !== null) {
            return $this->value->getUses();
        }

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
            return '$' . $this->name;
        }

        return sprintf('$%s = %s', $this->name, $this->value->render());
    }
}

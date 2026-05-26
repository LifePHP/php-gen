<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Type;

use Override;

class IntersectionType implements TypeInterface
{
    /**
     * @var ClassType[]
     */
    private array $types = [];

    public function add(ClassType $type): self
    {
        $this->types[] = $type;

        return $this;
    }

    #[Override]
    public function getUses(): array
    {
        $retVal = [];

        foreach ($this->types as $type) {
            foreach ($type->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return implode('&', array_map(
            fn (ClassType $type) => $type->getDocCommentPart(),
            $this->types,
        ));
    }

    #[Override]
    public function render(): string
    {
        return implode('&', array_map(
            fn (ClassType $type) => $type->render(),
            $this->types,
        ));
    }
}

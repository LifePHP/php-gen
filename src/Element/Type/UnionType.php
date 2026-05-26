<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Type;

use Override;

class UnionType implements TypeInterface
{
    /**
     * @var TypeInterface[]
     */
    private array $types = [];

    public function add(TypeInterface $type): self
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
        $intersectionFormat = count($this->types) > 1 ? '(%s)' : '%s';

        return implode('|', array_map(
            fn (TypeInterface $type) => $type instanceof IntersectionType
                ? sprintf($intersectionFormat, $type->getDocCommentPart())
                : $type->getDocCommentPart(),
            $this->types,
        ));
    }

    #[Override]
    public function render(): string
    {
        $intersectionFormat = count($this->types) > 1 ? '(%s)' : '%s';

        return implode('|', array_map(
            fn (TypeInterface $type) => $type instanceof IntersectionType
                ? sprintf($intersectionFormat, $type->render())
                : $type->render(),
            $this->types,
        ));
    }
}

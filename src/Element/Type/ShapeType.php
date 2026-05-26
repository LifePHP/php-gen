<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Type;

use Override;

class ShapeType implements TypeInterface
{
    /**
     * @var array<int|string, TypeInterface>
     */
    private array $shapes = [];

    public function add(int|string $key, TypeInterface $type): self
    {
        $this->shapes[$key] = $type;

        return $this;
    }

    #[Override]
    public function getUses(): array
    {
        $retVal = [];

        foreach ($this->shapes as $shape) {
            foreach ($shape->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        $parts = [];

        foreach ($this->shapes as $key => $type) {
            $parts[] = sprintf(
                '%s: %s',
                $key,
                $type->getDocCommentPart(),
            );
        }

        return sprintf(
            "array{\n    %s\n}",
            implode(",\n    ", $parts),
        );
    }

    #[Override]
    public function render(): string
    {
        return 'array';
    }
}

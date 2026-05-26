<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Literal;

use LifePhp\PhpGen\Element\Type\ShapeType;
use Override;

class ShapeLiteral extends Literal
{
    /**
     * @var array<array{key: ArrayKeyLiteral, value: LiteralInterface}>
     */
    private array $items = [];

    private ?ShapeType $cachedType = null;

    public function add(ArrayKeyLiteral $key, LiteralInterface $value): self
    {
        $this->items[] = ['key' => $key, 'value' => $value];
        $this->cachedType = null;

        return $this;
    }

    #[Override]
    public function getType(): ShapeType
    {
        if ($this->cachedType === null) {
            $shape = new ShapeType();

            foreach ($this->items as $item) {
                $shape->add($item['key']->getValue(), $item['value']->getType());
            }

            $this->cachedType = $shape;
        }

        return $this->cachedType;
    }

    #[Override]
    public function getUses(): array
    {
        $retVal = [];

        foreach ($this->items as $item) {
            foreach ($item['value']->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    #[Override]
    public function render(): string
    {
        if (empty($this->items)) {
            return '[]';
        }

        $parts = [];

        foreach ($this->items as $item) {
            $parts[] = sprintf('%s => %s', $item['key']->render(), $item['value']->render());
        }

        return sprintf(
            "[\n    %s\n]",
            implode(",\n    ", $parts),
        );
    }
}

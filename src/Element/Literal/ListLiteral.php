<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Literal;

use LifePhp\PhpGen\Element\Type\ListType;
use LifePhp\PhpGen\Element\Type\ScalarType;
use LifePhp\PhpGen\Element\Type\TypeInterface;
use LifePhp\PhpGen\Element\Type\UnionType;
use Override;

class ListLiteral extends Literal
{
    /**
     * @var LiteralInterface[]
     */
    private array $items = [];

    private ?ListType $cachedType = null;

    public function add(LiteralInterface $item): self
    {
        $this->items[] = $item;
        $this->cachedType = null;

        return $this;
    }

    #[Override]
    public function getType(): ListType
    {
        if ($this->cachedType === null) {
            $this->cachedType = new ListType($this->resolveValueType());
        }

        return $this->cachedType;
    }

    #[Override]
    public function getUses(): array
    {
        $retVal = [];

        foreach ($this->items as $item) {
            foreach ($item->getUses() as $use) {
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

        $parts = array_map(
            fn (LiteralInterface $item) => $item->render(),
            $this->items,
        );

        return sprintf(
            "[\n    %s\n]",
            implode(",\n    ", $parts),
        );
    }

    private function resolveValueType(): TypeInterface
    {
        $types = [];

        foreach ($this->items as $item) {
            $types[$item->getDocCommentPart()] = $item->getType();
        }

        if (count($types) === 0) {
            return ScalarType::Never;
        }

        if (count($types) === 1) {
            return array_first($types);
        }

        $union = new UnionType();

        foreach ($types as $type) {
            $union->add($type);
        }

        return $union;
    }
}
